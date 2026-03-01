import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

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

enum _VotacionTab { alcalde, concejal }

class _AlcaldeConcejalPageState extends State<AlcaldeConcejalPage> {
  final MobileAuthLocalStore _localStore = MobileAuthLocalStore.instance;
  final MobileVotacionService _service = MobileVotacionService();
  final ImagePicker _picker = ImagePicker();

  bool _loading = true;
  bool _saving = false;
  bool _syncing = false;
  bool _datosBloqueados = false;
  _VotacionTab _activeTab = _VotacionTab.alcalde;

  List<MobileMesa> _mesas = const [];
  List<Map<String, dynamic>> _partidos = const [];
  int? _mesaId;
  final Map<int, Uint8List?> _partidoIconBytes = {};

  final Map<int, TextEditingController> _gobCtrl = {};
  final Map<int, TextEditingController> _asdCtrl = {};
  final Map<int, TextEditingController> _aspCtrl = {};
  final Map<int, TextEditingController> _conCtrl = {};
  final Map<int, TextEditingController> _alcCtrl = {};

  final TextEditingController _bgCtrl = TextEditingController();
  final TextEditingController _ngCtrl = TextEditingController();
  final TextEditingController _basdCtrl = TextEditingController();
  final TextEditingController _nasdCtrl = TextEditingController();
  final TextEditingController _baspCtrl = TextEditingController();
  final TextEditingController _naspCtrl = TextEditingController();
  final TextEditingController _bconCtrl = TextEditingController();
  final TextEditingController _nconCtrl = TextEditingController();
  final TextEditingController _pnuConCtrl = TextEditingController();
  final TextEditingController _balcCtrl = TextEditingController();
  final TextEditingController _nalcCtrl = TextEditingController();
  final TextEditingController _pnuAlcCtrl = TextEditingController();
  final TextEditingController _obsCtrl = TextEditingController();

  final Map<String, String?> _localFotos = {
    for (final slot in votacionFotoSlots) slot: null,
  };

  static const List<Map<String, String>> _fotoAlcaldeConfig = [
    {'slot': 'foto1', 'label': 'Hoja trabajo - Alcalde'},
    {'slot': 'foto2', 'label': 'Acta electoral - Alcalde'},
  ];
  static const List<Map<String, String>> _fotoConcejalConfig = [
    {'slot': 'foto3', 'label': 'Hoja trabajo - Concejal'},
    {'slot': 'foto4', 'label': 'Acta electoral - Concejal'},
  ];
  static const List<Map<String, String>> _fotoComplementariasConfig = [
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
  }

  @override
  void dispose() {
    _bgCtrl.dispose();
    _ngCtrl.dispose();
    _basdCtrl.dispose();
    _nasdCtrl.dispose();
    _baspCtrl.dispose();
    _naspCtrl.dispose();
    _bconCtrl.dispose();
    _nconCtrl.dispose();
    _pnuConCtrl.dispose();
    _balcCtrl.dispose();
    _nalcCtrl.dispose();
    _pnuAlcCtrl.dispose();
    _obsCtrl.dispose();
    for (final m in [_gobCtrl, _asdCtrl, _aspCtrl, _conCtrl, _alcCtrl]) {
      for (final c in m.values) {
        c.dispose();
      }
    }
    super.dispose();
  }

  Future<void> _init() async {
    if (!mounted) return;
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
              'icono_base64': p.iconoBase64,
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

      if (!mounted) return;
      setState(() {
        _mesas = localMesas;
        _partidos = partidos;
        _mesaId = mesaId;
      });

      if (mesaId != null) {
        await _loadMesa(mesaId);
      }
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
      _gobCtrl.putIfAbsent(id, () => TextEditingController());
      _asdCtrl.putIfAbsent(id, () => TextEditingController());
      _aspCtrl.putIfAbsent(id, () => TextEditingController());
      _conCtrl.putIfAbsent(id, () => TextEditingController());
      _alcCtrl.putIfAbsent(id, () => TextEditingController());
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
  bool get _okCon =>
      _sumCon + _ival(_bconCtrl) + _ival(_nconCtrl) + _ival(_pnuConCtrl) ==
      250;
  bool get _okAlc =>
      _sumAlc + _ival(_balcCtrl) + _ival(_nalcCtrl) + _ival(_pnuAlcCtrl) ==
      250;

  bool get _allFotosReady {
    for (final slot in votacionFotoSlots) {
      if (!_hasFoto(slot)) return false;
    }
    return true;
  }

  bool get _fotosAlcaldeReady => _hasFoto('foto1') && _hasFoto('foto2');
  bool get _fotosConcejalReady => _hasFoto('foto3') && _hasFoto('foto4');
  bool get _allRequiredFotosReady => _fotosAlcaldeReady && _fotosConcejalReady;

  bool get _readyFinalizar => _mesaId != null;

  bool _hasFoto(String slot) {
    final localPath = _localFotos[slot];
    return localPath != null && localPath.isNotEmpty;
  }

  Future<Directory> _getVotacionCacheDir() async {
    final docs = await getApplicationDocumentsDirectory();
    final dir = Directory('${docs.path}/votacion/cache');
    await dir.create(recursive: true);
    return dir;
  }

  Uint8List? _decodePartidoIconBytes(Map<String, dynamic> partido) {
    final partidoId = _toInt(partido['id']) ?? 0;
    if (_partidoIconBytes.containsKey(partidoId)) {
      return _partidoIconBytes[partidoId];
    }
    final raw = partido['icono_base64']?.toString();
    if (raw == null || raw.isEmpty) {
      _partidoIconBytes[partidoId] = null;
      return null;
    }
    final commaIndex = raw.indexOf(',');
    final payload = commaIndex >= 0 ? raw.substring(commaIndex + 1) : raw;
    try {
      final bytes = base64Decode(payload);
      _partidoIconBytes[partidoId] = bytes;
      return bytes;
    } catch (_) {
      _partidoIconBytes[partidoId] = null;
      return null;
    }
  }

  String _cachedFotoPath({
    required Directory dir,
    required int mesaId,
    required String slot,
  }) {
    return '${dir.path}/mesa${mesaId}_$slot.jpg';
  }

  Future<void> _loadCachedFotosFromDisk(int mesaId) async {
    final dir = await _getVotacionCacheDir();
    for (final slot in votacionFotoSlots) {
      final path = _cachedFotoPath(dir: dir, mesaId: mesaId, slot: slot);
      if (await File(path).exists()) {
        _localFotos[slot] = path;
      }
    }
  }

  Future<void> _loadMesa(int mesaId) async {
    _resetForm();
    _datosBloqueados = false;
    final local = await _localStore.readVotacionDraft(mesaId);
    if (!mounted) return;
    if (local != null) {
      _applyDraft(local);
      _datosBloqueados =
          local.syncStatus == MobileAuthLocalStore.votacionSyncSynced;
      setState(() {});
      return;
    }

    await _loadCachedFotosFromDisk(mesaId);
    if (!mounted) return;
    setState(() {});
  }

  void _applyDraft(VotacionDraft d) {
    _obsCtrl.text = d.observacion ?? '';
    _setCtrlInt(_bgCtrl, d.blancosGobernador);
    _setCtrlInt(_ngCtrl, d.nulosGobernador);
    _setCtrlInt(_basdCtrl, d.blancosAsd);
    _setCtrlInt(_nasdCtrl, d.nulosAsd);
    _setCtrlInt(_baspCtrl, d.blancosAsp);
    _setCtrlInt(_naspCtrl, d.nulosAsp);
    _setCtrlInt(_bconCtrl, d.blancosConcejal);
    _setCtrlInt(_nconCtrl, d.nulosConcejal);
    _setCtrlInt(_pnuConCtrl, d.papeletasNoUtilizadasConcejal);
    _setCtrlInt(_balcCtrl, d.blancosAlcalde);
    _setCtrlInt(_nalcCtrl, d.nulosAlcalde);
    _setCtrlInt(_pnuAlcCtrl, d.papeletasNoUtilizadasAlcalde);
    for (final slot in votacionFotoSlots) {
      _localFotos[slot] = d.fotos[slot];
    }

    for (final v in d.votos) {
      _setCtrlInt(_gobCtrl[v.partidoId], v.votosGobernador);
      _setCtrlInt(_asdCtrl[v.partidoId], v.votosAsd);
      _setCtrlInt(_aspCtrl[v.partidoId], v.votosAsp);
      _setCtrlInt(_conCtrl[v.partidoId], v.votosConcejal);
      _setCtrlInt(_alcCtrl[v.partidoId], v.votosAlcalde);
    }
  }

  void _resetForm() {
    for (final m in [_gobCtrl, _asdCtrl, _aspCtrl, _conCtrl, _alcCtrl]) {
      for (final c in m.values) {
        c.text = '';
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
      _pnuConCtrl,
      _balcCtrl,
      _nalcCtrl,
      _pnuAlcCtrl,
    ]) {
      c.text = '';
    }
    _obsCtrl.text = '';
    for (final slot in votacionFotoSlots) {
      _localFotos[slot] = null;
    }
  }

  void _onDataChanged() {
    if (!_loading) {
      setState(() {});
    }
  }

  Future<void> _pickImage(String slot, String label) async {
    if (_datosBloqueados) {
      showError(context, 'Datos ya enviados. No se puede modificar.');
      return;
    }
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

    final dir = await _getVotacionCacheDir();
    final mesa = _mesaId ?? 0;
    final target = _cachedFotoPath(dir: dir, mesaId: mesa, slot: slot);
    await _service.compressImageToJpeg(
      sourcePath: picked.path,
      targetPath: target,
    );

    _localFotos[slot] = target;
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
        votosGobernador: 0,
        votosAsd: 0,
        votosAsp: 0,
        votosConcejal: _ivalOrZero(_conCtrl[id]),
        votosAlcalde: _ivalOrZero(_alcCtrl[id]),
      );
    }).toList();

    return VotacionDraft(
      mesaId: _mesaId ?? 0,
      finalizar: finalizar,
      observacion: _obsCtrl.text.trim().isEmpty ? null : _obsCtrl.text.trim(),
      blancosGobernador: 0,
      nulosGobernador: 0,
      blancosAsd: 0,
      nulosAsd: 0,
      blancosAsp: 0,
      nulosAsp: 0,
      blancosConcejal: _ival(_bconCtrl),
      nulosConcejal: _ival(_nconCtrl),
      papeletasNoUtilizadasConcejal: _ival(_pnuConCtrl),
      blancosAlcalde: _ival(_balcCtrl),
      nulosAlcalde: _ival(_nalcCtrl),
      papeletasNoUtilizadasAlcalde: _ival(_pnuAlcCtrl),
      votos: votos,
      fotos: Map<String, String?>.from(_localFotos),
      syncStatus: syncStatus,
      updatedAt: DateTime.now().toIso8601String(),
    );
  }

  Future<void> _finalizarYEnviar() async {
    if (_mesaId == null) {
      showError(context, 'Selecciona una mesa');
      return;
    }
    if (_datosBloqueados) {
      showError(context, 'Estos datos ya fueron enviados y bloqueados');
      return;
    }

    final hasActaElectoral = _hasFoto('foto2') || _hasFoto('foto4');
    if (!hasActaElectoral) {
      final continuarSinActa = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Sin acta electoral'),
          content: const Text(
            'No cargaste foto del acta electoral. Las fotos son opcionales.\n\nDeseas enviar de todas formas?',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Enviar igual'),
            ),
          ],
        ),
      );
      if (continuarSinActa != true) return;
    }

    if (!_okAlc || !_okCon) {
      final continuar = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Advertencia de totales'),
          content: Text(
            'Los totales no suman 250.\n'
            'Alcalde: ${_sumAlc + _ival(_balcCtrl) + _ival(_nalcCtrl) + _ival(_pnuAlcCtrl)}\n'
            'Concejal: ${_sumCon + _ival(_bconCtrl) + _ival(_nconCtrl) + _ival(_pnuConCtrl)}\n\n'
            'Deseas enviar de todas formas?',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Enviar igual'),
            ),
          ],
        ),
      );
      if (continuar != true) return;
    }

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar envio'),
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
        finalizar: false,
        syncStatus: MobileAuthLocalStore.votacionSyncLocal,
      );
      await _localStore.saveVotacionDraft(d);

      try {
        await _service.sendVotacion(d);
        await _localStore.markVotacionSynced(d.mesaId);
        if (mounted) {
          setState(() => _datosBloqueados = true);
          showSuccess(context, 'Votacion guardada y sincronizada');
        }
      } catch (e) {
        await _localStore.markVotacionError(d.mesaId, e.toString());
        if (mounted) {
          showError(context, _friendlySendError(e));
        }
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _syncPendientes({bool silent = false}) async {
    if (_syncing) return;
    if (!mounted) return;
    setState(() => _syncing = true);
    try {
      final pendings = await _localStore.readPendingVotacionDrafts();
      var ok = 0;
      var fail = 0;
      for (final d in pendings) {
        try {
          await _service.sendVotacion(d);
          await _localStore.markVotacionSynced(d.mesaId);
          ok++;
        } catch (e) {
          await _localStore.markVotacionError(d.mesaId, e.toString());
          fail++;
        }
      }
      if (!silent && mounted) {
        if (fail == 0) {
          showSuccess(context, 'Sincronizados: $ok');
        } else {
          showError(context, 'Sincronizados: $ok | Fallidos: $fail');
        }
      }
      if (mounted) setState(() {});
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  String _friendlySendError(Object e) {
    if (e is TimeoutException) {
      return 'Tiempo de espera agotado. Guardado local pendiente de sincronizacion';
    }
    if (e is SocketException) {
      return 'Sin internet: guardado local pendiente de sincronizacion';
    }
    final msg = e.toString().replaceFirst('StateError: ', '').trim();
    if (msg.isNotEmpty) {
      return 'No se pudo sincronizar ahora: $msg';
    }
    return 'No se pudo sincronizar ahora. Guardado local pendiente de sincronizacion';
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
        _buildTabs(),
        const SizedBox(height: 8),
        if (_activeTab == _VotacionTab.alcalde) ...[
          _buildCategoryCard(
            title: '1) Alcalde',
            voteMap: _alcCtrl,
            blancosCtrl: _balcCtrl,
            nulosCtrl: _nalcCtrl,
            papeletasNoUtilizadasCtrl: _pnuAlcCtrl,
            sum: _sumAlc,
            ok: _okAlc,
            editable: !_datosBloqueados,
          ),
          _buildFotosCard(
            title: 'Fotos - Alcalde',
            config: _fotoAlcaldeConfig,
            editable: !_datosBloqueados,
          ),
        ] else ...[
          _buildCategoryCard(
            title: '2) Concejal',
            voteMap: _conCtrl,
            blancosCtrl: _bconCtrl,
            nulosCtrl: _nconCtrl,
            papeletasNoUtilizadasCtrl: _pnuConCtrl,
            sum: _sumCon,
            ok: _okCon,
            editable: !_datosBloqueados,
          ),
          _buildFotosCard(
            title: 'Fotos - Concejal',
            config: _fotoConcejalConfig,
            editable: !_datosBloqueados,
          ),
        ],
        _buildGuardarMandarActions(),
        // _buildCategoryCard(
        //   title: '3) Gobernador',
        //   voteMap: _gobCtrl,
        //   blancosCtrl: _bgCtrl,
        //   nulosCtrl: _ngCtrl,
        //   papeletasNoUtilizadasCtrl: _pnuAlcCtrl,
        //   sum: _sumGob,
        //   ok: _okGob,
        // ),
        // _buildCategoryCard(
        //   title: '4) Asambleista por Distrito',
        //   voteMap: _asdCtrl,
        //   blancosCtrl: _basdCtrl,
        //   nulosCtrl: _nasdCtrl,
        //   papeletasNoUtilizadasCtrl: _pnuAlcCtrl,
        //   sum: _sumAsd,
        //   ok: _okAsd,
        // ),
        // _buildCategoryCard(
        //   title: '5) Asambleista por Poblacion',
        //   voteMap: _aspCtrl,
        //   blancosCtrl: _baspCtrl,
        //   nulosCtrl: _naspCtrl,
        //   papeletasNoUtilizadasCtrl: _pnuAlcCtrl,
        //   sum: _sumAsp,
        //   ok: _okAsp,
        // ),
        // _buildFotosCard(
        //   title: 'Fotos complementarias',
        //   config: _fotoComplementariasConfig,
        // ),
        // _buildGuardarMandarActions(),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(10),
            child: TextField(
              controller: _obsCtrl,
              enabled: !_datosBloqueados,
              maxLines: 3,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                labelText: 'Observacion',
              ),
              onChanged: (_) => _onDataChanged(),
            ),
          ),
        ),
        const SizedBox(height: 12),
      ],
    );
  }

  Widget _buildGuardarMandarActions() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FilledButton.icon(
            onPressed: _saving || !_readyFinalizar || _datosBloqueados
                ? null
                : _finalizarYEnviar,
            icon: const Icon(Icons.send_outlined),
            label: Text(
              _datosBloqueados
                  ? 'Ya enviado (bloqueado)'
                  : (_saving ? 'Procesando...' : 'Guardar y mandar'),
            ),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: _syncing ? null : () => _syncPendientes(),
            icon: const Icon(Icons.sync),
            label: Text(_syncing ? 'Sincronizando...' : 'Sincronizar pendientes'),
          ),
        ],
      ),
    );
  }

  Widget _buildTabs() {
    return Row(
      children: [
        Expanded(
          child: _tabButton(
            tab: _VotacionTab.alcalde,
            label: 'Alcalde',
            done: _datosBloqueados,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _tabButton(
            tab: _VotacionTab.concejal,
            label: 'Concejal',
            done: _datosBloqueados,
          ),
        ),
      ],
    );
  }

  Widget _tabButton({
    required _VotacionTab tab,
    required String label,
    required bool done,
  }) {
    final selected = _activeTab == tab;
    final bgColor = done
        ? const Color(0xFF2E7D32)
        : (selected ? const Color(0xFF1565C0) : const Color(0xFFE6EEF8));
    final fgColor = done || selected ? Colors.white : const Color(0xFF1B3A57);

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => setState(() => _activeTab = tab),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: Text(
            done ? '$label (Hecho)' : label,
            style: TextStyle(
              color: fgColor,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ),
    );
  }

  Widget _headerCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Row(
            //   children: [
            //     const Expanded(
            //       child: Text(
            //         'Subir votacion y asistencia',
            //         style: TextStyle(fontSize: 19, fontWeight: FontWeight.w700),
            //       ),
            //     ),
            //   ],
            // ),
            // const SizedBox(height: 8),
            // const Text('Mesas asignadas'),
            // const SizedBox(height: 6),
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
                            m.recintoNombre ?? '-',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 10),
                          ),
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
              'Control activo: solo Alcalde y Concejal. Funciona online/offline.',
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
    required TextEditingController papeletasNoUtilizadasCtrl,
    required int sum,
    required bool ok,
    required bool editable,
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
              final iconBytes = _decodePartidoIconBytes(p);
              return Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  children: [
                    if (iconBytes != null)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(4),
                        child: Image.memory(
                          iconBytes,
                          width: 24,
                          height: 24,
                          fit: BoxFit.cover,
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
                        enabled: editable,
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
                    enabled: editable,
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
                    enabled: editable,
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
            const SizedBox(height: 8),
            TextField(
              controller: papeletasNoUtilizadasCtrl,
              enabled: editable,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                labelText: 'Papeletas no utilizadas',
                isDense: true,
              ),
              onChanged: (_) => _onDataChanged(),
            ),
            const SizedBox(height: 6),
            Text(
              'Total ${sum + _ival(blancosCtrl) + _ival(nulosCtrl) + _ival(papeletasNoUtilizadasCtrl)}/250',
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

  Widget _buildFotosCard({
    required String title,
    required List<Map<String, String>> config,
    required bool editable,
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
            const SizedBox(height: 8),
            ...config.map((cfg) {
              final slot = cfg['slot']!;
              final label = cfg['label']!;
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    OutlinedButton.icon(
                      onPressed: editable ? () => _pickImage(slot, label) : null,
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

void _setCtrlInt(TextEditingController? c, int value) {
  if (c == null) return;
  c.text = value == 0 ? '' : '$value';
}

int? _toInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}
