import 'dart:async';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../../addons/snackbar_helper.dart';
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
  String? _asistenciaInfoMessage;

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

    if (!_canUseAsistencia) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF3E0),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFFFB74D)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.info_outline, color: Color(0xFFEF6C00)),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Asistencia no disponible',
                        style: TextStyle(
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
                  _asistenciaInfoMessage ??
                      'No tiene mesas asignadas. La asistencia no aplica para esta cuenta.',
                  style: const TextStyle(
                    fontSize: 14,
                    color: Color(0xFF8A4B00),
                    height: 1.35,
                  ),
                ),
              ],
            ),
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
