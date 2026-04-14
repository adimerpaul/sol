import 'dart:async';

import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../addons/snackbar_helper.dart';
import '../../models/mobile_login_response.dart';
import '../../services/mobile_asistencia_service.dart';
import '../../services/mobile_auth_local_store.dart';

class AsistenciaPage extends StatefulWidget {
  const AsistenciaPage({super.key, required this.sectionTitle});

  final String sectionTitle;

  @override
  State<AsistenciaPage> createState() => _AsistenciaPageState();
}

class _AsistenciaPageState extends State<AsistenciaPage> {
  final MobileAuthLocalStore _localStore = MobileAuthLocalStore.instance;
  final MobileAsistenciaService _service = MobileAsistenciaService();

  bool _loading = true;
  bool _syncing = false;
  bool _resolvingLocation = false;
  bool _hasPending = false;
  bool _canUseAsistencia = true;
  bool _showDelegadosTab = false;
  String? _asistenciaInfoMessage;
  List<MobileDelegadoAsistencia> _delegadosTitulares =
      const <MobileDelegadoAsistencia>[];
  List<MobileDelegadoAsistencia> _delegadosSuplentes =
      const <MobileDelegadoAsistencia>[];

  bool _avisoAntes = false;
  bool _avisoManana = false;
  bool _avisoMediodia = false;
  bool _avisoTarde = false;
  String? _horaAperturaMesa;

  final Set<String> _lockedFields = <String>{};
  Timer? _autoSyncTimer;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void dispose() {
    _autoSyncTimer?.cancel();
    super.dispose();
  }

  Future<void> _init() async {
    setState(() => _loading = true);
    try {
      final profile = await _localStore.readProfileData();
      final mesas = await _localStore.readMesasLocal();
      final hasMesas = mesas.isNotEmpty;
      final role = (profile?.user.role ?? '').trim();
      _showDelegadosTab =
          role == 'Administrador' ||
          role == 'Supervisor' ||
          role == 'Jefe de Recinto';
      _delegadosTitulares = profile?.asistenciaPanel.titulares ?? const [];
      _delegadosSuplentes = profile?.asistenciaPanel.suplentes ?? const [];

      _canUseAsistencia = hasMesas;
      if (!hasMesas) {
        _asistenciaInfoMessage = role.isEmpty
            ? 'No tiene mesas asignadas. La asistencia solo aplica a delegados con mesa.'
            : 'Usted es $role y no tiene mesas asignadas. La asistencia solo aplica a delegados con mesa.';
        _hasPending = false;
      } else {
        _asistenciaInfoMessage = null;
        _autoSyncTimer = Timer.periodic(const Duration(seconds: 25), (_) {
          _syncPending(silent: true);
        });
      }

      final local = await _localStore.readAsistenciaState();
      _applyState(local);
      if (_canUseAsistencia) {
        _hasPending = await _localStore.hasAsistenciaPendiente();
      }
    } catch (_) {
      // si falla algo local, dejamos defaults
    }

    if (mounted) {
      setState(() => _loading = false);
    }

    if (!_canUseAsistencia) {
      return;
    }

    if (_hasPending) {
      unawaited(_syncPending(silent: true));
    } else {
      unawaited(_refreshFromServer());
    }
  }

  Future<void> _refreshFromServer() async {
    try {
      final remote = await _service.fetchAsistenciaState();
      final state = Map<String, dynamic>.from(
        (remote['state'] as Map?) ?? const {},
      );
      await _localStore.saveAsistenciaState(
        avisoAntes: state['aviso_antes'] == true,
        avisoManana: state['aviso_manana'] == true,
        avisoMediodia: state['aviso_mediodia'] == true,
        avisoTarde: state['aviso_tarde'] == true,
        horaAperturaMesa: state['hora_apertura_mesa']?.toString(),
        etapa1: false,
        etapa2: false,
        syncStatus: MobileAuthLocalStore.asistenciaSyncSynced,
      );
      _applyState(state);
      if (mounted) setState(() {});
    } catch (_) {
      // offline: seguimos con local
    }
  }

  void _applyState(Map<String, dynamic> state) {
    _avisoAntes = state['aviso_antes'] == true;
    _avisoManana = state['aviso_manana'] == true;
    _avisoMediodia = state['aviso_mediodia'] == true;
    _avisoTarde = state['aviso_tarde'] == true;
    _horaAperturaMesa = state['hora_apertura_mesa']?.toString();

    _lockedFields.clear();
    if (_avisoAntes) _lockedFields.add('aviso_antes');
    if (_avisoManana) _lockedFields.add('aviso_manana');
    if (_avisoMediodia) _lockedFields.add('aviso_mediodia');
    if (_avisoTarde) _lockedFields.add('aviso_tarde');
  }

  Future<void> _toggleUpdate({
    required String field,
    required bool value,
  }) async {
    if (!_canUseAsistencia) return;
    if (_lockedFields.contains(field)) return;
    if (!value) return;

    String? horaForRequest;
    if (field == 'aviso_manana') {
      final selected = await _pickHoraApertura();
      if (selected == null) return;
      horaForRequest = selected;
      _horaAperturaMesa = selected;
    }

    final ok = await _confirmIrreversible();
    if (ok != true) return;

    double? latitud;
    double? longitud;
    String? presenteAt;
    if (field == 'aviso_antes') {
      try {
        setState(() => _resolvingLocation = true);
        final position = await _resolveQuickPosition();
        latitud = position.latitude;
        longitud = position.longitude;
        presenteAt = DateTime.now().toIso8601String();
      } catch (e) {
        if (!mounted) return;
        showError(context, e.toString().replaceFirst('Exception: ', ''));
        return;
      } finally {
        if (mounted) {
          setState(() => _resolvingLocation = false);
        }
      }
    }

    _lockedFields.add(field);
    _setLocalField(field, value);

    await _localStore.saveAsistenciaState(
      avisoAntes: _avisoAntes,
      avisoManana: _avisoManana,
      avisoMediodia: _avisoMediodia,
      avisoTarde: _avisoTarde,
      horaAperturaMesa: _horaAperturaMesa,
      etapa1: false,
      etapa2: false,
      syncStatus: MobileAuthLocalStore.asistenciaSyncLocal,
    );
    await _localStore.enqueueAsistenciaChange(
      field,
      value,
      horaAperturaMesa: horaForRequest,
      latitud: latitud,
      longitud: longitud,
      presenteAt: presenteAt,
    );
    setState(() => _hasPending = true);

    try {
      await _service.sendAsistenciaToggle(
        field: field,
        value: value,
        horaAperturaMesa: horaForRequest,
        latitud: latitud,
        longitud: longitud,
        presenteAt: presenteAt,
      );
      await _localStore.dequeueAsistenciaField(field);
      _hasPending = await _localStore.hasAsistenciaPendiente();
      await _localStore.saveAsistenciaState(
        avisoAntes: _avisoAntes,
        avisoManana: _avisoManana,
        avisoMediodia: _avisoMediodia,
        avisoTarde: _avisoTarde,
        horaAperturaMesa: _horaAperturaMesa,
        etapa1: false,
        etapa2: false,
        syncStatus: _hasPending
            ? MobileAuthLocalStore.asistenciaSyncLocal
            : MobileAuthLocalStore.asistenciaSyncSynced,
      );
      if (!mounted) return;
      setState(() {});
    } catch (_) {
      try {
        final reconciled = await _service.reconcileQueueWithServer();
        _hasPending = await _localStore.hasAsistenciaPendiente();
        final local = await _localStore.readAsistenciaState();
        _applyState(local);
        if (!mounted) return;
        setState(() {});
        if (reconciled) {
          showSuccess(context, 'Asistencia registrada y sincronizada');
          return;
        }
      } catch (_) {}

      if (!mounted) return;
      showError(context, 'Sin conexion. Quedo pendiente para sincronizar.');
    }
  }

  Future<Position> _resolveQuickPosition() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled) {
      throw Exception(
        'Active la ubicacion del telefono para registrar su mesa.',
      );
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw Exception('No se otorgo permiso de ubicacion.');
    }

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.medium,
        timeLimit: Duration(seconds: 8),
      ),
    );
  }

  Future<bool?> _confirmIrreversible() {
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar'),
        content: const Text(
          'Estas seguro? Una vez activado, este paso no se podra revertir.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Si, confirmar'),
          ),
        ],
      ),
    );
  }

  bool _isLocked(String field) => _lockedFields.contains(field);

  bool _canActivateTarde() {
    final now = TimeOfDay.now();
    // 16:30 = 4:30 PM
    return now.hour > 16 || (now.hour == 16 && now.minute >= 30);
  }

  void _setLocalField(String field, bool value) {
    setState(() {
      switch (field) {
        case 'aviso_antes':
          _avisoAntes = value;
          break;
        case 'aviso_manana':
          _avisoManana = value;
          break;
        case 'aviso_mediodia':
          _avisoMediodia = value;
          break;
        case 'aviso_tarde':
          _avisoTarde = value;
          break;
      }
    });
  }

  Future<String?> _pickHoraApertura() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 8, minute: 0),
    );
    if (picked == null) return null;

    final hh = picked.hour.toString().padLeft(2, '0');
    final mm = picked.minute.toString().padLeft(2, '0');
    final value = '$hh:$mm';
    if (!_horaValida(value)) {
      if (mounted) {
        showError(context, 'La hora debe estar entre 08:00 y 04:00');
      }
      return null;
    }
    return value;
  }

  bool _horaValida(String value) {
    final parts = value.split(':');
    if (parts.length != 2) return false;
    final hh = int.tryParse(parts[0]);
    final mm = int.tryParse(parts[1]);
    if (hh == null || mm == null || mm < 0 || mm > 59) return false;
    return hh >= 8 || hh <= 4;
  }

  Future<void> _syncPending({bool silent = false}) async {
    if (!_canUseAsistencia) return;
    if (_syncing) return;
    setState(() => _syncing = true);
    try {
      final count = await _service.flushQueue();
      _hasPending = await _localStore.hasAsistenciaPendiente();
      if (_hasPending) {
        try {
          await _service.reconcileQueueWithServer();
          _hasPending = await _localStore.hasAsistenciaPendiente();
          final local = await _localStore.readAsistenciaState();
          _applyState(local);
        } catch (_) {}
      }
      await _localStore.saveAsistenciaState(
        avisoAntes: _avisoAntes,
        avisoManana: _avisoManana,
        avisoMediodia: _avisoMediodia,
        avisoTarde: _avisoTarde,
        horaAperturaMesa: _horaAperturaMesa,
        etapa1: false,
        etapa2: false,
        syncStatus: _hasPending
            ? MobileAuthLocalStore.asistenciaSyncLocal
            : MobileAuthLocalStore.asistenciaSyncSynced,
      );
      if (!mounted) return;
      setState(() {});
      if (silent) return;
      if (_hasPending) {
        showError(context, 'Aun hay datos pendientes por enviar');
      } else {
        showSuccess(context, 'Sincronizados: $count');
      }
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    return DefaultTabController(
      length: _showDelegadosTab ? 2 : 1,
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TabBar(
              tabs: [
                const Tab(text: 'Mi Asistencia'),
                if (_showDelegadosTab) const Tab(text: 'Mis Delegados'),
              ],
            ),
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildMiAsistenciaTab(),
                if (_showDelegadosTab) _buildDelegadosTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMiAsistenciaTab() {
    if (!_canUseAsistencia) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildInfoCard(
            title: 'Asistencia no disponible',
            message:
                _asistenciaInfoMessage ??
                'No tiene mesas asignadas. La asistencia no aplica para esta cuenta.',
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_hasPending)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: FilledButton.icon(
              onPressed: _syncing ? null : _syncPending,
              icon: const Icon(Icons.pending_actions),
              label: Text(_syncing ? 'Sincronizando...' : 'Pendiente'),
            ),
          ),
        _buildSectionCard(
          title: 'En la mañana',
          pillColor: const Color(0xFF1E7A33),
          borderColor: const Color(0xFF62E59B),
          children: [
            _buildToggleTile(
              label: 'Estoy presente en mi mesa',
              field: 'aviso_antes',
              value: _avisoAntes,
              subtitle: _resolvingLocation
                  ? 'Espere, obteniendo latitud y longitud...'
                  : null,
            ),
            const SizedBox(height: 8),
            _buildToggleTile(
              label: 'Abri la mesa',
              field: 'aviso_manana',
              value: _avisoManana,
            ),
            if (_avisoManana && _horaAperturaMesa != null) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFF4F1F9),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.access_time,
                      size: 18,
                      color: Color(0xFF4E4A57),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Hora de apertura: $_horaAperturaMesa',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        const SizedBox(height: 14),
        _buildSectionCard(
          title: 'En la tarde-noche',
          pillColor: const Color(0xFFC62828),
          borderColor: const Color(0xFFFF7B7B),
          children: [
            _buildToggleTile(
              label: 'Tengo el acta de la gobernacion en mi poder',
              field: 'aviso_tarde',
              value: _avisoTarde,
              enabled: _canActivateTarde(),
            ),
            if (!_canActivateTarde() && !_isLocked('aviso_tarde'))
              Padding(
                padding: const EdgeInsets.only(top: 6, left: 4),
                child: Text(
                  'Disponible a partir de las 16:30',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.grey.shade500,
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildDelegadosTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildDelegadosSection(
          title: 'Delegados De Mesa Titulares',
          items: _delegadosTitulares,
          emptyText: 'No hay delegados titulares para mostrar.',
        ),
        const SizedBox(height: 16),
        _buildDelegadosSection(
          title: 'Delegados De Mesa Suplentes',
          items: _delegadosSuplentes,
          emptyText: 'No hay delegados suplentes para mostrar.',
        ),
      ],
    );
  }

  Widget _buildDelegadosSection({
    required String title,
    required List<MobileDelegadoAsistencia> items,
    required String emptyText,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE8DDF2)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x12000000),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Color(0xFF5F3DC4),
            ),
          ),
          const SizedBox(height: 12),
          if (items.isEmpty)
            Text(
              emptyText,
              style: const TextStyle(fontSize: 13, color: Color(0xFF7B6F87)),
            )
          else
            ...items.map(_buildDelegadoCard),
        ],
      ),
    );
  }

  Widget _buildDelegadoCard(MobileDelegadoAsistencia item) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF9F5FF),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE6DAF3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.name.isEmpty ? 'Sin nombre' : item.name,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF241B2F),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Mesa ${item.numeroMesa?.toString() ?? '-'}',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF6C5A7B),
                      ),
                    ),
                    if ((item.recintoNombre ?? '').trim().isNotEmpty)
                      Text(
                        item.recintoNombre!.trim(),
                        style: const TextStyle(
                          fontSize: 12,
                          color: Color(0xFF8D7A9B),
                        ),
                      ),
                  ],
                ),
              ),
              IconButton(
                visualDensity: VisualDensity.compact,
                tooltip: 'Abrir WhatsApp',
                onPressed: ((item.celular ?? '').trim().isEmpty)
                    ? null
                    : () => _openWhatsApp(item.celular),
                icon: const FaIcon(
                  FontAwesomeIcons.whatsapp,
                  color: Color(0xFF25D366),
                  size: 18,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildCheckChip('Mañana', item.avisoManana),
              _buildCheckChip('Mediodía', item.avisoMediodia),
              _buildCheckChip('Tarde', item.avisoTarde),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCheckChip(String label, bool checked) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: checked ? const Color(0xFFE4F7EA) : const Color(0xFFF1EDF5),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(
          color: checked ? const Color(0xFF7BC47F) : const Color(0xFFD9CFDF),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            checked ? Icons.check_circle : Icons.radio_button_unchecked,
            size: 16,
            color: checked ? const Color(0xFF2E7D32) : const Color(0xFF8E7E99),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: Color(0xFF4B3E58),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard({required String title, required String message}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF3E0),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFFB74D)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.info_outline, color: Color(0xFFEF6C00)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF8A4B00),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            message,
            style: const TextStyle(
              fontSize: 14,
              color: Color(0xFF8A4B00),
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openWhatsApp(String? rawPhone) async {
    final phone = _normalizePhone(rawPhone);
    if (phone == null) {
      return;
    }

    await launchUrl(
      Uri.parse('https://wa.me/$phone'),
      mode: LaunchMode.externalApplication,
    );
  }

  String? _normalizePhone(String? input) {
    if (input == null) return null;
    var digits = input.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.isEmpty) return null;
    if (!digits.startsWith('591')) {
      if (digits.length == 8) {
        digits = '591$digits';
      } else if (digits.length > 8) {
        digits = '591${digits.substring(digits.length - 8)}';
      }
    }
    return digits;
  }

  Widget _buildSectionCard({
    required String title,
    required Color pillColor,
    required Color borderColor,
    required List<Widget> children,
  }) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        Container(
          margin: const EdgeInsets.only(top: 18),
          padding: const EdgeInsets.fromLTRB(12, 22, 12, 12),
          decoration: BoxDecoration(
            color: const Color(0xFFF7F2FA),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: borderColor, width: 2),
          ),
          child: Column(children: children),
        ),
        Positioned(
          top: 0,
          left: 18,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: pillColor,
              borderRadius: BorderRadius.circular(14),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x22000000),
                  blurRadius: 8,
                  offset: Offset(0, 3),
                ),
              ],
            ),
            child: Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildToggleTile({
    required String label,
    required String field,
    required bool value,
    String? subtitle,
    bool enabled = true,
  }) {
    final locked = _isLocked(field);
    final isEnabled = !(locked || _resolvingLocation) && enabled;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.72),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w500,
                    color: isEnabled
                        ? const Color(0xFF3D3643)
                        : const Color(0xFFB7ACBC),
                  ),
                ),
                if (locked || subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    locked ? 'Bloqueado' : subtitle!,
                    style: TextStyle(
                      fontSize: 14,
                      color: locked
                          ? const Color(0xFF8F8595)
                          : const Color(0xFF7F7485),
                      fontWeight: locked ? FontWeight.w600 : FontWeight.w400,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 10),
          Transform.scale(
            scale: 1.05,
            child: Switch.adaptive(
              value: value,
              onChanged: isEnabled
                  ? (v) => _toggleUpdate(field: field, value: v)
                  : null,
            ),
          ),
        ],
      ),
    );
  }
}
