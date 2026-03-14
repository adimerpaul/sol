import 'dart:async';

import 'package:flutter/material.dart';

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
  bool _hasPending = false;

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
    _autoSyncTimer = Timer.periodic(const Duration(seconds: 25), (_) {
      _syncPending(silent: true);
    });
  }

  @override
  void dispose() {
    _autoSyncTimer?.cancel();
    super.dispose();
  }

  Future<void> _init() async {
    setState(() => _loading = true);
    try {
      final local = await _localStore.readAsistenciaState();
      _applyState(local);
      _hasPending = await _localStore.hasAsistenciaPendiente();
    } catch (_) {
      // si falla algo local, dejamos defaults
    }

    if (mounted) {
      setState(() => _loading = false);
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
    );
    setState(() => _hasPending = true);

    try {
      await _service.sendAsistenciaToggle(
        field: field,
        value: value,
        horaAperturaMesa: horaForRequest,
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
      if (!mounted) return;
      showError(context, 'Sin conexion. Quedo pendiente para sincronizar.');
    }
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
    if (_syncing) return;
    setState(() => _syncing = true);
    try {
      final count = await _service.flushQueue();
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

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.sectionTitle,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Asistencia: funciona offline y sincroniza cuando vuelve internet.',
                ),
                const SizedBox(height: 10),
                if (_hasPending)
                  FilledButton.icon(
                    onPressed: _syncing ? null : _syncPending,
                    icon: const Icon(Icons.pending_actions),
                    label: Text(_syncing ? 'Sincronizando...' : 'Pendiente'),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 8),
        _buildToggleTile(
          label: 'Estoy presente en mi mesa',
          field: 'aviso_antes',
          value: _avisoAntes,
        ),
        _buildToggleTile(
          label: 'Abri la mesa',
          field: 'aviso_manana',
          value: _avisoManana,
        ),
        if (_avisoManana && _horaAperturaMesa != null)
          ListTile(
            dense: true,
            leading: const Icon(Icons.access_time),
            title: Text('Hora de apertura: $_horaAperturaMesa'),
          ),
        _buildToggleTile(
          label: 'Tengo el acta de la alcaldia en mi poder',
          field: 'aviso_mediodia',
          value: _avisoMediodia,
        ),
        _buildToggleTile(
          label: 'Tengo el acta de la gobernacion en mi poder',
          field: 'aviso_tarde',
          value: _avisoTarde,
        ),
      ],
    );
  }

  Widget _buildToggleTile({
    required String label,
    required String field,
    required bool value,
  }) {
    final locked = _isLocked(field);
    return SwitchListTile.adaptive(
      title: Text(label),
      subtitle: locked ? const Text('Bloqueado') : null,
      value: value,
      onChanged: locked ? null : (v) => _toggleUpdate(field: field, value: v),
    );
  }
}
