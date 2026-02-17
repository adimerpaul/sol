import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';

import '../../addons/snackbar_helper.dart';
import '../../models/mobile_login_response.dart';
import '../../models/votacion_model.dart';
import '../../services/mobile_auth_local_store.dart';
import '../../services/mobile_votacion_service.dart';

class AlcaldeConcejalPage extends StatefulWidget {
  const AlcaldeConcejalPage({super.key});

  @override
  State<AlcaldeConcejalPage> createState() => _AlcaldeConcejalPageState();
}

class _AlcaldeConcejalPageState extends State<AlcaldeConcejalPage> {
  final MobileAuthLocalStore _localStore = MobileAuthLocalStore.instance;
  final MobileVotacionService _service = MobileVotacionService();
  final ImagePicker _picker = ImagePicker();

  bool _loading = true;
  bool _saving = false;
  bool _syncing = false;
  bool _hasPending = false;

  Timer? _autoSaveDebounce;
  Timer? _autoSyncTimer;

  List<MobileMesa> _mesas = const [];
  List<Map<String, dynamic>> _partidos = const [];
  int? _mesaId;

  final Map<int, TextEditingController> _gobCtrl = {};
  final Map<int, TextEditingController> _asdCtrl = {};
  final Map<int, TextEditingController> _aspCtrl = {};
  final Map<int, TextEditingController> _conCtrl = {};
  final Map<int, TextEditingController> _alcCtrl = {};

  final TextEditingController _bgCtrl = TextEditingController(text: '0');
  final TextEditingController _ngCtrl = TextEditingController(text: '0');
  final TextEditingController _basdCtrl = TextEditingController(text: '0');
  final TextEditingController _nasdCtrl = TextEditingController(text: '0');
  final TextEditingController _baspCtrl = TextEditingController(text: '0');
  final TextEditingController _naspCtrl = TextEditingController(text: '0');
  final TextEditingController _bconCtrl = TextEditingController(text: '0');
  final TextEditingController _nconCtrl = TextEditingController(text: '0');
  final TextEditingController _balcCtrl = TextEditingController(text: '0');
  final TextEditingController _nalcCtrl = TextEditingController(text: '0');
  final TextEditingController _obsCtrl = TextEditingController();

  final Map<String, String?> _localFotos = {
    for (final slot in votacionFotoSlots) slot: null,
  };
  final Map<String, String?> _serverFotos = {
    for (final slot in votacionFotoSlots) slot: null,
  };

  static const List<Map<String, String>> _fotoConfig = [
    {'slot': 'foto1', 'label': 'Hoja trabajo - Alcalde'},
    {'slot': 'foto2', 'label': 'Acta electoral - Alcalde'},
    {'slot': 'foto3', 'label': 'Hoja trabajo - Concejal'},
    {'slot': 'foto4', 'label': 'Acta electoral - Concejal'},
    {'slot': 'foto5', 'label': 'Hoja trabajo - Gobernador'},
    {'slot': 'foto6', 'label': 'Acta electoral - Gobernador'},
    {'slot': 'foto7', 'label': 'Hoja trabajo - Asam. Distrito'},
    {'slot': 'foto8', 'label': 'Acta electoral - Asam. Distrito'},
    {'slot': 'foto9', 'label': 'Hoja trabajo - Asam. Poblacion'},
    {'slot': 'foto10', 'label': 'Acta electoral - Asam. Poblacion'},
  ];

  @override
  void initState() {
    super.initState();
    _init();
    _autoSyncTimer = Timer.periodic(const Duration(seconds: 25), (_) {
      _syncPendientes(silent: true);
    });
  }

  @override
  void dispose() {
    _autoSaveDebounce?.cancel();
    _autoSyncTimer?.cancel();

    _bgCtrl.dispose();
    _ngCtrl.dispose();
    _basdCtrl.dispose();
    _nasdCtrl.dispose();
    _baspCtrl.dispose();
    _naspCtrl.dispose();
    _bconCtrl.dispose();
    _nconCtrl.dispose();
    _balcCtrl.dispose();
    _nalcCtrl.dispose();
    _obsCtrl.dispose();
    for (final m in [_gobCtrl, _asdCtrl, _aspCtrl, _conCtrl, _alcCtrl]) {
      for (final c in m.values) {
        c.dispose();
      }
    }
    super.dispose();
  }

  Future<void> _init() async {
    setState(() => _loading = true);
    try {
      final localMesas = await _localStore.readMesasLocal();
      final localPartidos = await _localStore.readPartidosLocal();
      var partidos = localPartidos
          .map(
            (p) => {
              'id': p.id,
              'sigla': p.sigla,
              'nombre': p.nombre,
              'icono_url': p.iconoUrl,
              'orden_municipal': p.ordenMunicipal,
              'orden_departamental': p.ordenDepartamental,
            },
          )
          .toList();

      if (partidos.isEmpty) {
        try {
          final catalogo = await _service.loadCatalogo();
          partidos = ((catalogo['partidos'] as List?) ?? const [])
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
        } catch (_) {}
      }

      if (partidos.isEmpty) {
        partidos = await _buildPartidosFromLocalDrafts();
      }

      _ensureControllers(partidos);

      int? mesaId = _mesaId;
      if (mesaId == null && localMesas.isNotEmpty) {
        mesaId = localMesas.first.id;
      }

      _hasPending = (await _localStore.readPendingVotacionDrafts()).isNotEmpty;

      setState(() {
        _mesas = localMesas;
        _partidos = partidos;
        _mesaId = mesaId;
      });

      if (mesaId != null) {
        await _loadMesa(mesaId);
      }

      unawaited(_syncPendientes(silent: true));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<List<Map<String, dynamic>>> _buildPartidosFromLocalDrafts() async {
    final pending = await _localStore.readPendingVotacionDrafts();
    if (pending.isEmpty) return const [];
    final set = <int, Map<String, dynamic>>{};
    for (final d in pending) {
      for (final v in d.votos) {
        set[v.partidoId] = {
          'id': v.partidoId,
          'sigla': v.sigla,
          'nombre': v.nombre,
          'icono_url': v.iconoUrl,
          'orden_municipal': 0,
          'orden_departamental': 0,
        };
      }
    }
    return set.values.toList();
  }

  void _ensureControllers(List<Map<String, dynamic>> partidos) {
    for (final p in partidos) {
      final id = _toInt(p['id']) ?? 0;
      _gobCtrl.putIfAbsent(id, () => TextEditingController(text: '0'));
      _asdCtrl.putIfAbsent(id, () => TextEditingController(text: '0'));
      _aspCtrl.putIfAbsent(id, () => TextEditingController(text: '0'));
      _conCtrl.putIfAbsent(id, () => TextEditingController(text: '0'));
      _alcCtrl.putIfAbsent(id, () => TextEditingController(text: '0'));
    }
  }

  List<Map<String, dynamic>> get _partidosSorted {
    final list = _partidos.map((e) => Map<String, dynamic>.from(e)).toList();
    list.sort((a, b) {
      final oa = _toInt(a['orden_municipal']) ?? 0;
      final ob = _toInt(b['orden_municipal']) ?? 0;
      if (oa != ob) return oa.compareTo(ob);
      return (a['sigla'] ?? '').toString().compareTo(
        (b['sigla'] ?? '').toString(),
      );
    });
    return list;
  }

  int _ival(TextEditingController c) => int.tryParse(c.text.trim()) ?? 0;

  int _sum(Map<int, TextEditingController> map) {
    var s = 0;
    for (final p in _partidosSorted) {
      final id = _toInt(p['id']) ?? 0;
      final c = map[id];
      if (c != null) s += _ival(c);
    }
    return s;
  }

  int get _sumGob => _sum(_gobCtrl);
  int get _sumAsd => _sum(_asdCtrl);
  int get _sumAsp => _sum(_aspCtrl);
  int get _sumCon => _sum(_conCtrl);
  int get _sumAlc => _sum(_alcCtrl);

  bool get _okGob => _sumGob + _ival(_bgCtrl) + _ival(_ngCtrl) == 250;
  bool get _okAsd => _sumAsd + _ival(_basdCtrl) + _ival(_nasdCtrl) == 250;
  bool get _okAsp => _sumAsp + _ival(_baspCtrl) + _ival(_naspCtrl) == 250;
  bool get _okCon => _sumCon + _ival(_bconCtrl) + _ival(_nconCtrl) == 250;
  bool get _okAlc => _sumAlc + _ival(_balcCtrl) + _ival(_nalcCtrl) == 250;

  bool get _allFotosReady {
    for (final slot in votacionFotoSlots) {
      if (!_hasFoto(slot)) return false;
    }
    return true;
  }

  bool get _readyFinalizar =>
      _mesaId != null &&
      _partidos.isNotEmpty &&
      _okGob &&
      _okAsd &&
      _okAsp &&
      _okCon &&
      _okAlc &&
      _allFotosReady;

  bool _hasFoto(String slot) {
    final localPath = _localFotos[slot];
    if (localPath != null && localPath.isNotEmpty) return true;
    final serverPath = _serverFotos[slot];
    return serverPath != null && serverPath.isNotEmpty;
  }

  Future<void> _loadMesa(int mesaId) async {
    _resetForm();
    final local = await _localStore.readVotacionDraft(mesaId);
    if (local != null) {
      _applyDraft(local);
      setState(() {});
      return;
    }

    try {
      final remote = await _service.loadMesa(mesaId);
      final resultado = remote['resultado'];
      if (resultado is! Map) {
        setState(() {});
        return;
      }
      final r = Map<String, dynamic>.from(resultado);
      _bgCtrl.text = '${_toInt(r['blancos_gobernador']) ?? 0}';
      _ngCtrl.text = '${_toInt(r['nulos_gobernador']) ?? 0}';
      _basdCtrl.text = '${_toInt(r['blancos_asambleista_distrito']) ?? 0}';
      _nasdCtrl.text = '${_toInt(r['nulos_asambleista_distrito']) ?? 0}';
      _baspCtrl.text = '${_toInt(r['blancos_asambleista_poblacion']) ?? 0}';
      _naspCtrl.text = '${_toInt(r['nulos_asambleista_poblacion']) ?? 0}';
      _bconCtrl.text = '${_toInt(r['blancos_concejal']) ?? 0}';
      _nconCtrl.text = '${_toInt(r['nulos_concejal']) ?? 0}';
      _balcCtrl.text = '${_toInt(r['blancos_alcalde']) ?? 0}';
      _nalcCtrl.text = '${_toInt(r['nulos_alcalde']) ?? 0}';
      _obsCtrl.text = (r['observacion'] ?? '').toString();

      for (final slot in votacionFotoSlots) {
        _serverFotos[slot] = r['${slot}_url']?.toString();
      }

      final detalles = (r['detalles'] as List?) ?? const [];
      for (final d in detalles.whereType<Map>()) {
        final m = Map<String, dynamic>.from(d);
        final id = _toInt(m['partido_id']) ?? 0;
        _gobCtrl[id]?.text = '${_toInt(m['votos_gobernador']) ?? 0}';
        _asdCtrl[id]?.text = '${_toInt(m['votos_asambleista_distrito']) ?? 0}';
        _aspCtrl[id]?.text = '${_toInt(m['votos_asambleista_poblacion']) ?? 0}';
        _conCtrl[id]?.text = '${_toInt(m['votos_concejal']) ?? 0}';
        _alcCtrl[id]?.text = '${_toInt(m['votos_alcalde']) ?? 0}';
      }
    } catch (_) {}
    setState(() {});
  }

  void _applyDraft(VotacionDraft d) {
    _obsCtrl.text = d.observacion ?? '';
    _bgCtrl.text = '${d.blancosGobernador}';
    _ngCtrl.text = '${d.nulosGobernador}';
    _basdCtrl.text = '${d.blancosAsd}';
    _nasdCtrl.text = '${d.nulosAsd}';
    _baspCtrl.text = '${d.blancosAsp}';
    _naspCtrl.text = '${d.nulosAsp}';
    _bconCtrl.text = '${d.blancosConcejal}';
    _nconCtrl.text = '${d.nulosConcejal}';
    _balcCtrl.text = '${d.blancosAlcalde}';
    _nalcCtrl.text = '${d.nulosAlcalde}';
    for (final slot in votacionFotoSlots) {
      _localFotos[slot] = d.fotos[slot];
    }

    for (final v in d.votos) {
      _gobCtrl[v.partidoId]?.text = '${v.votosGobernador}';
      _asdCtrl[v.partidoId]?.text = '${v.votosAsd}';
      _aspCtrl[v.partidoId]?.text = '${v.votosAsp}';
      _conCtrl[v.partidoId]?.text = '${v.votosConcejal}';
      _alcCtrl[v.partidoId]?.text = '${v.votosAlcalde}';
    }
  }

  void _resetForm() {
    for (final m in [_gobCtrl, _asdCtrl, _aspCtrl, _conCtrl, _alcCtrl]) {
      for (final c in m.values) {
        c.text = '0';
      }
    }
    for (final c in [
      _bgCtrl,
      _ngCtrl,
      _basdCtrl,
      _nasdCtrl,
      _baspCtrl,
      _naspCtrl,
      _bconCtrl,
      _nconCtrl,
      _balcCtrl,
      _nalcCtrl,
    ]) {
      c.text = '0';
    }
    _obsCtrl.text = '';
    for (final slot in votacionFotoSlots) {
      _localFotos[slot] = null;
      _serverFotos[slot] = null;
    }
  }

  void _onDataChanged() {
    if (!_loading) {
      setState(() {});
      _scheduleAutoSave();
    }
  }

  void _scheduleAutoSave() {
    if (_mesaId == null) return;
    _autoSaveDebounce?.cancel();
    _autoSaveDebounce = Timer(const Duration(milliseconds: 700), () {
      _saveDraftLocal(
        finalizar: false,
        syncStatus: MobileAuthLocalStore.votacionSyncLocal,
      );
    });
  }

  Future<void> _saveDraftLocal({
    required bool finalizar,
    required String syncStatus,
  }) async {
    if (_mesaId == null) return;
    final d = _buildDraft(finalizar: finalizar, syncStatus: syncStatus);
    await _localStore.saveVotacionDraft(d);
    _hasPending = true;
    if (mounted) setState(() {});
  }

  Future<void> _pickImage(String slot, String label) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Tomar foto'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Elegir de galeria'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;

    final picked = await _picker.pickImage(source: source, imageQuality: 85);
    if (picked == null) return;

    final docs = await getApplicationDocumentsDirectory();
    final dir = Directory('${docs.path}/votacion');
    await dir.create(recursive: true);
    final mesa = _mesaId ?? 0;
    final fileName =
        '${slot}_mesa${mesa}_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final target = '${dir.path}/$fileName';
    await _service.compressImageToJpeg(
      sourcePath: picked.path,
      targetPath: target,
    );

    _localFotos[slot] = target;
    _serverFotos[slot] = null;
    _onDataChanged();

    if (mounted) {
      showSuccess(context, '$label guardada localmente');
    }
  }

  VotacionDraft _buildDraft({
    required bool finalizar,
    required String syncStatus,
  }) {
    final votos = _partidosSorted.map((p) {
      final id = _toInt(p['id']) ?? 0;
      return VotoPartidoItem(
        partidoId: id,
        sigla: (p['sigla'] ?? '').toString(),
        nombre: (p['nombre'] ?? '').toString(),
        iconoUrl: p['icono_url']?.toString(),
        votosGobernador: _ivalOrZero(_gobCtrl[id]),
        votosAsd: _ivalOrZero(_asdCtrl[id]),
        votosAsp: _ivalOrZero(_aspCtrl[id]),
        votosConcejal: _ivalOrZero(_conCtrl[id]),
        votosAlcalde: _ivalOrZero(_alcCtrl[id]),
      );
    }).toList();

    return VotacionDraft(
      mesaId: _mesaId ?? 0,
      finalizar: finalizar,
      observacion: _obsCtrl.text.trim().isEmpty ? null : _obsCtrl.text.trim(),
      blancosGobernador: _ival(_bgCtrl),
      nulosGobernador: _ival(_ngCtrl),
      blancosAsd: _ival(_basdCtrl),
      nulosAsd: _ival(_nasdCtrl),
      blancosAsp: _ival(_baspCtrl),
      nulosAsp: _ival(_naspCtrl),
      blancosConcejal: _ival(_bconCtrl),
      nulosConcejal: _ival(_nconCtrl),
      blancosAlcalde: _ival(_balcCtrl),
      nulosAlcalde: _ival(_nalcCtrl),
      votos: votos,
      fotos: Map<String, String?>.from(_localFotos),
      syncStatus: syncStatus,
      updatedAt: DateTime.now().toIso8601String(),
    );
  }

  Future<void> _finalizarYEnviar() async {
    if (!_readyFinalizar) {
      showError(context, 'Completa datos, valida 250 y carga las 10 fotos');
      return;
    }

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar finalizacion'),
        content: const Text(
          'Se enviaran datos de votacion. Si no hay internet quedaran pendientes para sincronizar.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (ok != true) return;

    setState(() => _saving = true);
    try {
      final d = _buildDraft(
        finalizar: true,
        syncStatus: MobileAuthLocalStore.votacionSyncLocal,
      );
      await _localStore.saveVotacionDraft(d);

      try {
        await _service.sendVotacion(d);
        await _localStore.markVotacionSynced(d.mesaId);
        _hasPending =
            (await _localStore.readPendingVotacionDrafts()).isNotEmpty;
        if (mounted) showSuccess(context, 'Votacion finalizada y sincronizada');
      } catch (e) {
        await _localStore.markVotacionError(d.mesaId, e.toString());
        _hasPending = true;
        if (mounted) {
          showError(
            context,
            'Sin internet: guardado local y pendiente de sincronizacion',
          );
        }
      }

      await _init();
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _syncPendientes({bool silent = false}) async {
    if (_syncing) return;
    setState(() => _syncing = true);
    try {
      final pendings = await _localStore.readPendingVotacionDrafts();
      var ok = 0;
      for (final d in pendings) {
        try {
          await _service.sendVotacion(d);
          await _localStore.markVotacionSynced(d.mesaId);
          ok++;
        } catch (e) {
          await _localStore.markVotacionError(d.mesaId, e.toString());
        }
      }
      _hasPending = (await _localStore.readPendingVotacionDrafts()).isNotEmpty;
      if (!silent && mounted) {
        showSuccess(context, 'Sincronizados: $ok');
      }
      if (mounted) setState(() {});
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_mesas.isEmpty) {
      return const Center(
        child: Text('No hay mesas asignadas para este delegado'),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        _headerCard(),
        const SizedBox(height: 8),
        _buildCategoryCard(
          title: '1) Alcalde',
          voteMap: _alcCtrl,
          blancosCtrl: _balcCtrl,
          nulosCtrl: _nalcCtrl,
          sum: _sumAlc,
          ok: _okAlc,
        ),
        _buildCategoryCard(
          title: '2) Concejal',
          voteMap: _conCtrl,
          blancosCtrl: _bconCtrl,
          nulosCtrl: _nconCtrl,
          sum: _sumCon,
          ok: _okCon,
        ),
        _buildCategoryCard(
          title: '3) Gobernador',
          voteMap: _gobCtrl,
          blancosCtrl: _bgCtrl,
          nulosCtrl: _ngCtrl,
          sum: _sumGob,
          ok: _okGob,
        ),
        _buildCategoryCard(
          title: '4) Asambleista por Distrito',
          voteMap: _asdCtrl,
          blancosCtrl: _basdCtrl,
          nulosCtrl: _nasdCtrl,
          sum: _sumAsd,
          ok: _okAsd,
        ),
        _buildCategoryCard(
          title: '5) Asambleista por Poblacion',
          voteMap: _aspCtrl,
          blancosCtrl: _baspCtrl,
          nulosCtrl: _naspCtrl,
          sum: _sumAsp,
          ok: _okAsp,
        ),
        _buildFotosCard(),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(10),
            child: TextField(
              controller: _obsCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                labelText: 'Observacion',
              ),
              onChanged: (_) => _onDataChanged(),
            ),
          ),
        ),
        const SizedBox(height: 8),
        FilledButton(
          onPressed: _saving || !_readyFinalizar ? null : _finalizarYEnviar,
          child: Text(_saving ? 'Procesando...' : 'Finalizar y enviar'),
        ),
        const SizedBox(height: 12),
      ],
    );
  }

  Widget _headerCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Subir votacion y asistencia',
                    style: TextStyle(fontSize: 19, fontWeight: FontWeight.w700),
                  ),
                ),
                if (_hasPending)
                  Chip(
                    avatar: const Icon(Icons.pending_actions, size: 18),
                    label: Text(_syncing ? 'Sincronizando...' : 'Pendiente'),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            const Text('Mesas asignadas'),
            const SizedBox(height: 6),
            SizedBox(
              height: 76,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: _mesas.length,
                itemBuilder: (context, i) {
                  final m = _mesas[i];
                  final selected = m.id == _mesaId;
                  final local = (m.estadoLocal ?? 'PENDIENTE').toUpperCase();
                  final color = local == 'REALIZADO'
                      ? Colors.green
                      : (local == 'LOCAL' ? Colors.orange : Colors.grey);
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(
                          color: selected ? Colors.cyan : color,
                          width: selected ? 2 : 1,
                        ),
                      ),
                      onPressed: () async {
                        final id = m.id;
                        if (id == null) return;
                        setState(() => _mesaId = id);
                        await _loadMesa(id);
                      },
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('Mesa ${m.numeroMesa ?? '-'}'),
                          Text(
                            local,
                            style: TextStyle(fontSize: 11, color: color),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Control: cada categoria debe sumar 250. Fotos requeridas: 10.',
              style: TextStyle(color: Colors.grey.shade700, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryCard({
    required String title,
    required Map<int, TextEditingController> voteMap,
    required TextEditingController blancosCtrl,
    required TextEditingController nulosCtrl,
    required int sum,
    required bool ok,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
            ),
            const SizedBox(height: 6),
            ..._partidosSorted.map((p) {
              final id = _toInt(p['id']) ?? 0;
              final icon = p['icono_url']?.toString();
              return Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  children: [
                    if (icon != null && icon.isNotEmpty)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: Image.network(
                          icon,
                          width: 24,
                          height: 24,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(Icons.flag, size: 20),
                        ),
                      )
                    else
                      const Icon(Icons.flag, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        '${p['sigla'] ?? ''} - ${p['nombre'] ?? ''}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    SizedBox(
                      width: 95,
                      child: TextField(
                        controller: voteMap[id],
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          border: OutlineInputBorder(),
                          labelText: 'Votos',
                          isDense: true,
                        ),
                        onChanged: (_) => _onDataChanged(),
                      ),
                    ),
                  ],
                ),
              );
            }),
            const SizedBox(height: 6),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: blancosCtrl,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      labelText: 'Blancos',
                      isDense: true,
                    ),
                    onChanged: (_) => _onDataChanged(),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: nulosCtrl,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      labelText: 'Nulos',
                      isDense: true,
                    ),
                    onChanged: (_) => _onDataChanged(),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'Total ${sum + _ival(blancosCtrl) + _ival(nulosCtrl)}/250',
              style: TextStyle(
                color: ok ? Colors.green : Colors.red,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFotosCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Fotos (10)',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
            ),
            const SizedBox(height: 8),
            ..._fotoConfig.map((cfg) {
              final slot = cfg['slot']!;
              final label = cfg['label']!;
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    OutlinedButton.icon(
                      onPressed: () => _pickImage(slot, label),
                      icon: const Icon(Icons.add_a_photo_outlined),
                      label: Text(label),
                    ),
                    const SizedBox(height: 4),
                    _photoPreview(slot),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _photoPreview(String slot) {
    final localPath = _localFotos[slot];
    if (localPath != null && localPath.isNotEmpty) {
      return _imgFile(localPath);
    }
    final remotePath = _serverFotos[slot];
    if (remotePath != null && remotePath.isNotEmpty) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Image.network(
          remotePath,
          height: 130,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) => _emptyImage(),
        ),
      );
    }
    return _emptyImage();
  }

  Widget _emptyImage() {
    return Container(
      height: 90,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Text('Sin imagen'),
    );
  }

  Widget _imgFile(String path) {
    return Padding(
      padding: const EdgeInsets.only(top: 2),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Image.file(File(path), height: 130, fit: BoxFit.cover),
      ),
    );
  }
}

int _ivalOrZero(TextEditingController? c) {
  if (c == null) return 0;
  return int.tryParse(c.text.trim()) ?? 0;
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}
