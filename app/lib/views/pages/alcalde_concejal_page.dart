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

enum _VotacionTab {
  alcalde,
  concejal,
  gobernador,
  asambleistaDistrito,
  asambleistaPoblacion,
}

class _AlcaldeConcejalPageState extends State<AlcaldeConcejalPage> {
  static const int _targetTotalPorCategoria = 250;
  static const int _maxTotalPermitidoPorCategoria = 260;

  final MobileAuthLocalStore _localStore = MobileAuthLocalStore.instance;
  final MobileVotacionService _service = MobileVotacionService();
  final ImagePicker _picker = ImagePicker();

  bool _loading = true;
  bool _saving = false;
  bool _syncing = false;
  bool _datosBloqueados = false;
  _VotacionTab _activeTab = _VotacionTab.alcalde;
  final Map<_VotacionTab, bool> _tabLocks = {
    _VotacionTab.alcalde: false,
    _VotacionTab.concejal: false,
    _VotacionTab.gobernador: false,
    _VotacionTab.asambleistaDistrito: false,
    _VotacionTab.asambleistaPoblacion: false,
  };

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
  final TextEditingController _pnuGobCtrl = TextEditingController();
  final TextEditingController _basdCtrl = TextEditingController();
  final TextEditingController _nasdCtrl = TextEditingController();
  final TextEditingController _pnuAsdCtrl = TextEditingController();
  final TextEditingController _baspCtrl = TextEditingController();
  final TextEditingController _naspCtrl = TextEditingController();
  final TextEditingController _pnuAspCtrl = TextEditingController();
  final TextEditingController _bconCtrl = TextEditingController();
  final TextEditingController _nconCtrl = TextEditingController();
  final TextEditingController _pnuConCtrl = TextEditingController();
  final TextEditingController _balcCtrl = TextEditingController();
  final TextEditingController _nalcCtrl = TextEditingController();
  final TextEditingController _pnuAlcCtrl = TextEditingController();
  final TextEditingController _obsAlcCtrl = TextEditingController();
  final TextEditingController _obsConCtrl = TextEditingController();
  final TextEditingController _obsGobCtrl = TextEditingController();
  final TextEditingController _obsAsdCtrl = TextEditingController();
  final TextEditingController _obsAspCtrl = TextEditingController();

  final Map<String, String?> _localFotos = {
    for (final slot in votacionFotoSlots) slot: null,
  };
  DateTime? _lastLimitAlertAt;

  static const List<Map<String, String>> _fotoAlcaldeConfig = [
    {'slot': 'foto1', 'label': 'Hoja trabajo - Alcalde'},
    {'slot': 'foto2', 'label': 'Acta electoral - Alcalde'},
  ];
  static const List<Map<String, String>> _fotoConcejalConfig = [
    {'slot': 'foto3', 'label': 'Hoja trabajo - Concejal'},
    {'slot': 'foto4', 'label': 'Acta electoral - Concejal'},
  ];
  static const List<Map<String, String>> _fotoGobernadorConfig = [
    {'slot': 'foto5', 'label': 'Hoja trabajo - Gobernador'},
    {'slot': 'foto6', 'label': 'Acta electoral - Gobernador'},
  ];
  static const List<Map<String, String>> _fotoAsdConfig = [
    {'slot': 'foto7', 'label': 'Hoja trabajo - Asam. Distrito'},
    {'slot': 'foto8', 'label': 'Acta electoral - Asam. Distrito'},
  ];
  static const List<Map<String, String>> _fotoAspConfig = [
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
    _pnuGobCtrl.dispose();
    _basdCtrl.dispose();
    _nasdCtrl.dispose();
    _pnuAsdCtrl.dispose();
    _baspCtrl.dispose();
    _naspCtrl.dispose();
    _pnuAspCtrl.dispose();
    _bconCtrl.dispose();
    _nconCtrl.dispose();
    _pnuConCtrl.dispose();
    _balcCtrl.dispose();
    _nalcCtrl.dispose();
    _pnuAlcCtrl.dispose();
    _obsAlcCtrl.dispose();
    _obsConCtrl.dispose();
    _obsGobCtrl.dispose();
    _obsAsdCtrl.dispose();
    _obsAspCtrl.dispose();
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
              'color': p.color,
              'icono_url': p.iconoUrl,
              'icono_base64': p.iconoBase64,
              'orden_municipal': p.ordenMunicipal,
              'orden_departamental': p.ordenDepartamental,
              'habilitado_gobernador': p.habilitadoGobernador,
              'habilitado_asambleista_poblacion':
                  p.habilitadoAsambleistaPoblacion,
              'habilitado_asambleista_distrito':
                  p.habilitadoAsambleistaDistrito,
              'habilitado_concejal': p.habilitadoConcejal,
              'habilitado_alcalde': p.habilitadoAlcalde,
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
          'color': null,
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

  List<Map<String, dynamic>> _partidosSortedBy(String orderField) {
    final list = _partidos.map((e) => Map<String, dynamic>.from(e)).toList();
    list.sort((a, b) {
      final oa = _toInt(a[orderField]) ?? 0;
      final ob = _toInt(b[orderField]) ?? 0;
      if (oa != ob) return oa.compareTo(ob);
      return (a['sigla'] ?? '').toString().compareTo(
        (b['sigla'] ?? '').toString(),
      );
    });
    return list;
  }

  List<Map<String, dynamic>> get _partidosMunicipales =>
      _partidosSortedBy('orden_municipal');

  List<Map<String, dynamic>> get _partidosDepartamentales =>
      _partidosSortedBy('orden_departamental');

  List<Map<String, dynamic>> get _partidosGobernador =>
      _partidosDepartamentales
          .where((p) => p['habilitado_gobernador'] != false)
          .toList();

  List<Map<String, dynamic>> get _partidosAsd => _partidosDepartamentales
      .where((p) => p['habilitado_asambleista_distrito'] != false)
      .toList();

  List<Map<String, dynamic>> get _partidosAsp => _partidosDepartamentales
      .where((p) => p['habilitado_asambleista_poblacion'] != false)
      .toList();

  List<Map<String, dynamic>> get _partidosConcejal =>
      _partidosMunicipales
          .where((p) => p['habilitado_concejal'] != false)
          .toList();

  List<Map<String, dynamic>> get _partidosAlcalde =>
      _partidosMunicipales
          .where((p) => p['habilitado_alcalde'] != false)
          .toList();

  int _ival(TextEditingController c) => int.tryParse(c.text.trim()) ?? 0;

  int _sum(
    Map<int, TextEditingController> map,
    List<Map<String, dynamic>> partidos,
  ) {
    var s = 0;
    for (final p in partidos) {
      final id = _toInt(p['id']) ?? 0;
      final c = map[id];
      if (c != null) s += _ival(c);
    }
    return s;
  }

  int get _sumGob => _sum(_gobCtrl, _partidosGobernador);
  int get _sumAsd => _sum(_asdCtrl, _partidosAsd);
  int get _sumAsp => _sum(_aspCtrl, _partidosAsp);
  int get _sumCon => _sum(_conCtrl, _partidosConcejal);
  int get _sumAlc => _sum(_alcCtrl, _partidosAlcalde);

  bool get _okGob =>
      _sumGob + _ival(_bgCtrl) + _ival(_ngCtrl) + _ival(_pnuGobCtrl) ==
      _targetTotalPorCategoria;
  bool get _okAsd =>
      _sumAsd + _ival(_basdCtrl) + _ival(_nasdCtrl) + _ival(_pnuAsdCtrl) ==
      _targetTotalPorCategoria;
  bool get _okAsp =>
      _sumAsp + _ival(_baspCtrl) + _ival(_naspCtrl) + _ival(_pnuAspCtrl) ==
      _targetTotalPorCategoria;
  bool get _okCon =>
      _sumCon + _ival(_bconCtrl) + _ival(_nconCtrl) + _ival(_pnuConCtrl) ==
      _targetTotalPorCategoria;
  bool get _okAlc =>
      _sumAlc + _ival(_balcCtrl) + _ival(_nalcCtrl) + _ival(_pnuAlcCtrl) ==
      _targetTotalPorCategoria;

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
  bool get _hasVotosCargados {
    final total =
        _sumGob +
        _sumAsd +
        _sumAsp +
        _sumCon +
        _sumAlc +
        _ival(_bgCtrl) +
        _ival(_ngCtrl) +
        _ival(_pnuGobCtrl) +
        _ival(_basdCtrl) +
        _ival(_nasdCtrl) +
        _ival(_pnuAsdCtrl) +
        _ival(_baspCtrl) +
        _ival(_naspCtrl) +
        _ival(_pnuAspCtrl) +
        _ival(_bconCtrl) +
        _ival(_nconCtrl) +
        _ival(_pnuConCtrl) +
        _ival(_balcCtrl) +
        _ival(_nalcCtrl) +
        _ival(_pnuAlcCtrl);
    return total > 0;
  }

  MobileMesa? get _mesaActual {
    final id = _mesaId;
    if (id == null) return null;
    for (final m in _mesas) {
      if (m.id == id) return m;
    }
    return null;
  }

  String _estadoMesaLabel(MobileMesa? mesa) {
    final estadoApi = (mesa?.estado ?? '').toUpperCase().trim();
    if (estadoApi.isNotEmpty) return estadoApi;
    final local = (mesa?.estadoLocal ?? 'PENDIENTE').toUpperCase().trim();
    if (local == 'REALIZADO') return 'FINALIZADA';
    if (local == 'LOCAL') return 'EN_PROCESO';
    return 'PENDIENTE';
  }

  bool get _mesaEsFinalizada => _estadoMesaLabel(_mesaActual) == 'FINALIZADA';
  bool get _mesaEditablePorEstado {
    final estado = _estadoMesaLabel(_mesaActual);
    return estado == 'ASIGNADA' || estado == 'EN_PROCESO';
  }

  bool _tabBloqueada(_VotacionTab tab) => _tabLocks[tab] == true;

  bool _tabEditable(_VotacionTab tab) =>
      !_datosBloqueados && !_tabBloqueada(tab) && _mesaEditablePorEstado;

  bool get _activeTabBloqueada => _tabBloqueada(_activeTab);

  bool get _allTabsBloqueadas => _tabLocks.values.every((v) => v);

  String _tabLabel(_VotacionTab tab) {
    switch (tab) {
      case _VotacionTab.alcalde:
        return 'Alcalde';
      case _VotacionTab.concejal:
        return 'Concejal';
      case _VotacionTab.gobernador:
        return 'Gobernador';
      case _VotacionTab.asambleistaDistrito:
        return 'Asambleista por Distrito';
      case _VotacionTab.asambleistaPoblacion:
        return 'Asambleista por Poblacion';
    }
  }

  int _tabTotalActual(_VotacionTab tab) {
    switch (tab) {
      case _VotacionTab.alcalde:
        return _sumAlc + _ival(_balcCtrl) + _ival(_nalcCtrl) + _ival(_pnuAlcCtrl);
      case _VotacionTab.concejal:
        return _sumCon + _ival(_bconCtrl) + _ival(_nconCtrl) + _ival(_pnuConCtrl);
      case _VotacionTab.gobernador:
        return _sumGob + _ival(_bgCtrl) + _ival(_ngCtrl) + _ival(_pnuGobCtrl);
      case _VotacionTab.asambleistaDistrito:
        return _sumAsd + _ival(_basdCtrl) + _ival(_nasdCtrl) + _ival(_pnuAsdCtrl);
      case _VotacionTab.asambleistaPoblacion:
        return _sumAsp + _ival(_baspCtrl) + _ival(_naspCtrl) + _ival(_pnuAspCtrl);
    }
  }

  bool _tabTotalesOk(_VotacionTab tab) =>
      _tabTotalActual(tab) == _targetTotalPorCategoria;

  void _showLimitAlert() {
    final now = DateTime.now();
    final last = _lastLimitAlertAt;
    if (last != null && now.difference(last).inMilliseconds < 900) return;
    _lastLimitAlertAt = now;
    showError(
      context,
      'El total por categoria no puede exceder $_maxTotalPermitidoPorCategoria votos.',
    );
  }

  void _setNumericControllerValue(TextEditingController controller, int value) {
    final safeValue = value <= 0 ? '' : value.toString();
    controller.value = TextEditingValue(
      text: safeValue,
      selection: TextSelection.collapsed(offset: safeValue.length),
    );
  }

  void _enforceCategoryLimit(
    _VotacionTab tab,
    TextEditingController controller,
  ) {
    final current = _ival(controller);
    final otherValues = _tabTotalActual(tab) - current;
    final maxAllowed = (_maxTotalPermitidoPorCategoria - otherValues).clamp(
      0,
      _maxTotalPermitidoPorCategoria,
    ) as int;
    if (current <= maxAllowed) {
      _onDataChanged();
      return;
    }
    _setNumericControllerValue(controller, maxAllowed);
    _onDataChanged();
    _showLimitAlert();
  }

  String? _tabActaSlot(_VotacionTab tab) {
    switch (tab) {
      case _VotacionTab.alcalde:
        return 'foto2';
      case _VotacionTab.concejal:
        return 'foto4';
      case _VotacionTab.gobernador:
        return 'foto6';
      case _VotacionTab.asambleistaDistrito:
        return 'foto8';
      case _VotacionTab.asambleistaPoblacion:
        return 'foto10';
    }
  }

  List<String> _tabFotoSlotsParaBloqueo(_VotacionTab tab) {
    switch (tab) {
      case _VotacionTab.alcalde:
        return const ['foto1', 'foto2'];
      case _VotacionTab.concejal:
        return const ['foto3', 'foto4'];
      case _VotacionTab.gobernador:
        return const ['foto5', 'foto6'];
      case _VotacionTab.asambleistaDistrito:
        return const ['foto7', 'foto8'];
      case _VotacionTab.asambleistaPoblacion:
        return const ['foto9', 'foto10'];
    }
  }

  bool _tabTieneFotosCompletas(_VotacionTab tab) {
    final slots = _tabFotoSlotsParaBloqueo(tab);
    return slots.every(_hasFoto);
  }

  void _lockTab(_VotacionTab tab) {
    _tabLocks[tab] = true;
  }

  void _unlockAllTabs() {
    for (final t in _tabLocks.keys) {
      _tabLocks[t] = false;
    }
  }

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
    return '${dir.path}/mesa${mesaId}_$slot.webp';
  }

  Future<void> _loadCachedFotosFromDisk(int mesaId) async {
    final dir = await _getVotacionCacheDir();
    for (final slot in votacionFotoSlots) {
      final webpPath = _cachedFotoPath(dir: dir, mesaId: mesaId, slot: slot);
      if (await File(webpPath).exists()) {
        _localFotos[slot] = webpPath;
        continue;
      }
      final legacyJpgPath = '${dir.path}/mesa${mesaId}_$slot.jpg';
      if (await File(legacyJpgPath).exists()) {
        _localFotos[slot] = legacyJpgPath;
      }
    }
  }

  Future<void> _loadMesa(int mesaId) async {
    _resetForm();
    _unlockAllTabs();
    _datosBloqueados = _mesaEsFinalizada;
    if (_mesaEsFinalizada) {
      for (final t in _tabLocks.keys) {
        _tabLocks[t] = true;
      }
    }

    try {
      final remote = await _service.loadMesa(mesaId);
      final partidos = ((remote['partidos'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
      if (partidos.isNotEmpty) {
        _ensureControllers(partidos);
        _partidos = partidos;
      }
    } catch (_) {}

    final local = await _localStore.readVotacionDraft(mesaId);
    if (!mounted) return;
    if (local != null) {
      _applyDraft(local);
      _tabLocks[_VotacionTab.alcalde] = local.lockAlcalde;
      _tabLocks[_VotacionTab.concejal] = local.lockConcejal;
      _tabLocks[_VotacionTab.gobernador] = local.lockGobernador;
      _tabLocks[_VotacionTab.asambleistaDistrito] = local.lockAsd;
      _tabLocks[_VotacionTab.asambleistaPoblacion] = local.lockAsp;
      _datosBloqueados = _mesaEsFinalizada || local.finalizar;
      if (_datosBloqueados) {
        for (final t in _tabLocks.keys) {
          _tabLocks[t] = true;
        }
      }
      setState(() {});
      return;
    }

    await _loadCachedFotosFromDisk(mesaId);
    if (!mounted) return;
    setState(() {});
  }

  void _applyDraft(VotacionDraft d) {
    _obsAlcCtrl.text = d.observacionAlcalde ?? '';
    _obsConCtrl.text = d.observacionConcejal ?? '';
    _obsGobCtrl.text = d.observacionGobernador ?? '';
    _obsAsdCtrl.text = d.observacionAsd ?? '';
    _obsAspCtrl.text = d.observacionAsp ?? '';
    _setCtrlInt(_bgCtrl, d.blancosGobernador);
    _setCtrlInt(_ngCtrl, d.nulosGobernador);
    _setCtrlInt(_pnuGobCtrl, d.papeletasNoUtilizadasGobernador);
    _setCtrlInt(_basdCtrl, d.blancosAsd);
    _setCtrlInt(_nasdCtrl, d.nulosAsd);
    _setCtrlInt(_pnuAsdCtrl, d.papeletasNoUtilizadasAsd);
    _setCtrlInt(_baspCtrl, d.blancosAsp);
    _setCtrlInt(_naspCtrl, d.nulosAsp);
    _setCtrlInt(_pnuAspCtrl, d.papeletasNoUtilizadasAsp);
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
      _pnuGobCtrl,
      _basdCtrl,
      _nasdCtrl,
      _pnuAsdCtrl,
      _baspCtrl,
      _naspCtrl,
      _pnuAspCtrl,
      _bconCtrl,
      _nconCtrl,
      _pnuConCtrl,
      _balcCtrl,
      _nalcCtrl,
      _pnuAlcCtrl,
    ]) {
      c.text = '';
    }
    _obsAlcCtrl.text = '';
    _obsConCtrl.text = '';
    _obsGobCtrl.text = '';
    _obsAsdCtrl.text = '';
    _obsAspCtrl.text = '';
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
    if (_activeTabBloqueada) {
      showError(context, 'La pestaña ${_tabLabel(_activeTab)} ya esta bloqueada.');
      return;
    }
    if (!_mesaEditablePorEstado) {
      showError(context, 'Solo puedes editar mesas ASIGNADA o EN_PROCESO');
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

    final picked = await _picker.pickImage(source: source, imageQuality: 100);
    if (picked == null) return;

    final dir = await _getVotacionCacheDir();
    final mesa = _mesaId ?? 0;
    final target = _cachedFotoPath(dir: dir, mesaId: mesa, slot: slot);
    await _service.compressImageToWebp(
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
    final votos = _partidos.map((p) {
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
      observacion: null,
      observacionGobernador: _obsGobCtrl.text.trim().isEmpty ? null : _obsGobCtrl.text.trim(),
      observacionAsd: _obsAsdCtrl.text.trim().isEmpty ? null : _obsAsdCtrl.text.trim(),
      observacionAsp: _obsAspCtrl.text.trim().isEmpty ? null : _obsAspCtrl.text.trim(),
      observacionConcejal: _obsConCtrl.text.trim().isEmpty ? null : _obsConCtrl.text.trim(),
      observacionAlcalde: _obsAlcCtrl.text.trim().isEmpty ? null : _obsAlcCtrl.text.trim(),
      blancosGobernador: _ival(_bgCtrl),
      nulosGobernador: _ival(_ngCtrl),
      papeletasNoUtilizadasGobernador: _ival(_pnuGobCtrl),
      blancosAsd: _ival(_basdCtrl),
      nulosAsd: _ival(_nasdCtrl),
      papeletasNoUtilizadasAsd: _ival(_pnuAsdCtrl),
      blancosAsp: _ival(_baspCtrl),
      nulosAsp: _ival(_naspCtrl),
      papeletasNoUtilizadasAsp: _ival(_pnuAspCtrl),
      blancosConcejal: _ival(_bconCtrl),
      nulosConcejal: _ival(_nconCtrl),
      papeletasNoUtilizadasConcejal: _ival(_pnuConCtrl),
      blancosAlcalde: _ival(_balcCtrl),
      nulosAlcalde: _ival(_nalcCtrl),
      papeletasNoUtilizadasAlcalde: _ival(_pnuAlcCtrl),
      lockAlcalde: _tabLocks[_VotacionTab.alcalde] == true,
      lockConcejal: _tabLocks[_VotacionTab.concejal] == true,
      lockGobernador: _tabLocks[_VotacionTab.gobernador] == true,
      lockAsd: _tabLocks[_VotacionTab.asambleistaDistrito] == true,
      lockAsp: _tabLocks[_VotacionTab.asambleistaPoblacion] == true,
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
    if (_activeTabBloqueada) {
      showError(context, 'La pestaña ${_tabLabel(_activeTab)} ya esta bloqueada.');
      return;
    }
    if (!_mesaEditablePorEstado) {
      showError(context, 'Solo puedes editar mesas ASIGNADA o EN_PROCESO');
      return;
    }

    final actaSlot = _tabActaSlot(_activeTab);
    final hasActaElectoral =
        actaSlot == null ? true : _hasFoto(actaSlot);
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

    if (!_tabTotalesOk(_activeTab)) {
      final continuar = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Advertencia de totales'),
          content: Text(
            '${_tabLabel(_activeTab)} no suma $_targetTotalPorCategoria.\n'
            'Total actual: ${_tabTotalActual(_activeTab)}\n\n'
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
      final bloqueaPestanaAhora = _tabTieneFotosCompletas(_activeTab);
      if (bloqueaPestanaAhora) {
        _lockTab(_activeTab);
      }
      final finalizaAhora = _allTabsBloqueadas;
      final d = _buildDraft(
        finalizar: finalizaAhora,
        syncStatus: MobileAuthLocalStore.votacionSyncLocal,
      );
      await _localStore.saveVotacionDraft(d);
      if (mounted) {
        setState(() {
          _datosBloqueados = d.finalizar;
        });
      }

      try {
        await _service.sendVotacion(d);
        await _localStore.markVotacionSynced(
          d.mesaId,
          finalizada: d.finalizar,
        );
        if (mounted) {
          final mesas = _mesas.map((m) {
            if (m.id != d.mesaId) return m;
            return MobileMesa(
              id: m.id,
              idOriginal: m.idOriginal,
              recintoId: m.recintoId,
              numeroMesa: m.numeroMesa,
              estado: d.finalizar ? 'FINALIZADA' : 'EN_PROCESO',
              estadoLocal: d.finalizar ? 'REALIZADO' : 'PENDIENTE',
              recintoNombre: m.recintoNombre,
              localidadNombre: m.localidadNombre,
              municipioNombre: m.municipioNombre,
              provinciaNombre: m.provinciaNombre,
              departamentoNombre: m.departamentoNombre,
              recintoLatitud: m.recintoLatitud,
              recintoLongitud: m.recintoLongitud,
              resultado: m.resultado,
            );
          }).toList();
          setState(() {
            _mesas = mesas;
            _datosBloqueados = d.finalizar;
            if (_datosBloqueados) {
              for (final t in _tabLocks.keys) {
                _tabLocks[t] = true;
              }
            }
          });
          showSuccess(
            context,
            d.finalizar
                ? 'Votacion finalizada y sincronizada'
                : (bloqueaPestanaAhora
                    ? 'Pestaña ${_tabLabel(_activeTab)} bloqueada y sincronizada'
                    : 'Datos sincronizados. Falta hoja y/o acta para bloquear ${_tabLabel(_activeTab)}'),
          );
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
          await _localStore.markVotacionSynced(
            d.mesaId,
            finalizada: d.finalizar,
          );
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
            tab: _VotacionTab.alcalde,
            partidos: _partidosAlcalde,
            voteMap: _alcCtrl,
            blancosCtrl: _balcCtrl,
            nulosCtrl: _nalcCtrl,
            papeletasNoUtilizadasCtrl: _pnuAlcCtrl,
            sum: _sumAlc,
            ok: _okAlc,
            editable: _tabEditable(_VotacionTab.alcalde),
          ),
          _buildFotosCard(
            title: 'Fotos - Alcalde',
            config: _fotoAlcaldeConfig,
            editable: _tabEditable(_VotacionTab.alcalde),
          ),
          _buildObsCard(
            _obsAlcCtrl,
            editable: _tabEditable(_VotacionTab.alcalde),
          ),
        ] else if (_activeTab == _VotacionTab.concejal) ...[
          _buildCategoryCard(
            title: '2) Concejal',
            tab: _VotacionTab.concejal,
            partidos: _partidosConcejal,
            voteMap: _conCtrl,
            blancosCtrl: _bconCtrl,
            nulosCtrl: _nconCtrl,
            papeletasNoUtilizadasCtrl: _pnuConCtrl,
            sum: _sumCon,
            ok: _okCon,
            editable: _tabEditable(_VotacionTab.concejal),
          ),
          _buildFotosCard(
            title: 'Fotos - Concejal',
            config: _fotoConcejalConfig,
            editable: _tabEditable(_VotacionTab.concejal),
          ),
          _buildObsCard(
            _obsConCtrl,
            editable: _tabEditable(_VotacionTab.concejal),
          ),
        ] else if (_activeTab == _VotacionTab.gobernador) ...[
          _buildCategoryCard(
            title: '3) Gobernador',
            tab: _VotacionTab.gobernador,
            partidos: _partidosGobernador,
            voteMap: _gobCtrl,
            blancosCtrl: _bgCtrl,
            nulosCtrl: _ngCtrl,
            papeletasNoUtilizadasCtrl: _pnuGobCtrl,
            sum: _sumGob,
            ok: _okGob,
            editable: _tabEditable(_VotacionTab.gobernador),
          ),
          _buildFotosCard(
            title: 'Fotos - Gobernador',
            config: _fotoGobernadorConfig,
            editable: _tabEditable(_VotacionTab.gobernador),
          ),
          _buildObsCard(
            _obsGobCtrl,
            editable: _tabEditable(_VotacionTab.gobernador),
          ),
        ] else if (_activeTab == _VotacionTab.asambleistaDistrito) ...[
          _buildCategoryCard(
            title: '4) Asambleista por Distrito',
            tab: _VotacionTab.asambleistaDistrito,
            partidos: _partidosAsd,
            voteMap: _asdCtrl,
            blancosCtrl: _basdCtrl,
            nulosCtrl: _nasdCtrl,
            papeletasNoUtilizadasCtrl: _pnuAsdCtrl,
            sum: _sumAsd,
            ok: _okAsd,
            editable: _tabEditable(_VotacionTab.asambleistaDistrito),
          ),
          _buildFotosCard(
            title: 'Fotos - Asambleista por Distrito',
            config: _fotoAsdConfig,
            editable: _tabEditable(_VotacionTab.asambleistaDistrito),
          ),
          _buildObsCard(
            _obsAsdCtrl,
            editable: _tabEditable(_VotacionTab.asambleistaDistrito),
          ),
        ] else ...[
          _buildCategoryCard(
            title: '5) Asambleista por Poblacion',
            tab: _VotacionTab.asambleistaPoblacion,
            partidos: _partidosAsp,
            voteMap: _aspCtrl,
            blancosCtrl: _baspCtrl,
            nulosCtrl: _naspCtrl,
            papeletasNoUtilizadasCtrl: _pnuAspCtrl,
            sum: _sumAsp,
            ok: _okAsp,
            editable: _tabEditable(_VotacionTab.asambleistaPoblacion),
          ),
          _buildFotosCard(
            title: 'Fotos - Asambleista por Poblacion',
            config: _fotoAspConfig,
            editable: _tabEditable(_VotacionTab.asambleistaPoblacion),
          ),
          _buildObsCard(
            _obsAspCtrl,
            editable: _tabEditable(_VotacionTab.asambleistaPoblacion),
          ),
        ],
        _buildGuardarMandarActions(),
        const SizedBox(height: 12),
      ],
    );
  }

  Widget _buildObsCard(
    TextEditingController controller, {
    required bool editable,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: TextField(
          controller: controller,
          enabled: editable,
          maxLines: 3,
          decoration: const InputDecoration(
            border: OutlineInputBorder(),
            labelText: 'Observacion',
          ),
          onChanged: (_) => _onDataChanged(),
        ),
      ),
    );
  }

  Widget _buildGuardarMandarActions() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FilledButton.icon(
            onPressed:
                _saving ||
                    !_readyFinalizar ||
                    _datosBloqueados ||
                    _activeTabBloqueada ||
                    !_mesaEditablePorEstado
                ? null
                : _finalizarYEnviar,
            icon: const Icon(Icons.send_outlined),
            label: Text(
              _datosBloqueados
                  ? 'Ya enviado (bloqueado)'
                  : _activeTabBloqueada
                  ? '${_tabLabel(_activeTab)} bloqueado'
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
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _tabButton(
            tab: _VotacionTab.alcalde,
            label: 'Alcalde',
            done: _tabBloqueada(_VotacionTab.alcalde),
          ),
          const SizedBox(width: 8),
          _tabButton(
            tab: _VotacionTab.concejal,
            label: 'Concejal',
            done: _tabBloqueada(_VotacionTab.concejal),
          ),
          const SizedBox(width: 8),
          _tabButton(
            tab: _VotacionTab.gobernador,
            label: 'Gobernador',
            done: _tabBloqueada(_VotacionTab.gobernador),
          ),
          const SizedBox(width: 8),
          _tabButton(
            tab: _VotacionTab.asambleistaDistrito,
            label: 'Asam. Distrito',
            done: _tabBloqueada(_VotacionTab.asambleistaDistrito),
          ),
          const SizedBox(width: 8),
          _tabButton(
            tab: _VotacionTab.asambleistaPoblacion,
            label: 'Asam. Poblacion',
            done: _tabBloqueada(_VotacionTab.asambleistaPoblacion),
          ),
        ],
      ),
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
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14),
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
    final mesaActual = _mesaActual;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (mesaActual != null) ...[
              Text(
                '${mesaActual.departamentoNombre ?? '-'} · ${mesaActual.provinciaNombre ?? '-'} · ${mesaActual.municipioNombre ?? '-'}',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Colors.blueGrey.shade700,
                ),
              ),
              const SizedBox(height: 6),
            ],
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
                  final estado = _estadoMesaLabel(m);
                  final color = estado == 'FINALIZADA'
                      ? Colors.green
                      : (estado == 'EN_PROCESO'
                            ? Colors.orange
                            : (estado == 'ASIGNADA'
                                  ? Colors.blue
                                  : Colors.grey));
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
                            estado,
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
              'Bloqueo por pestaña: se bloquea cuando tiene hoja y acta de esa categoria.',
              style: TextStyle(color: Colors.grey.shade700, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryCard({
    required String title,
    required _VotacionTab tab,
    required List<Map<String, dynamic>> partidos,
    required Map<int, TextEditingController> voteMap,
    required TextEditingController blancosCtrl,
    required TextEditingController nulosCtrl,
    required TextEditingController papeletasNoUtilizadasCtrl,
    required int sum,
    required bool ok,
    required bool editable,
    bool showPnu = true,
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
            ...partidos.map((p) {
              final id = _toInt(p['id']) ?? 0;
              final iconBytes = _decodePartidoIconBytes(p);
              final partidoColor = _partidoColor(p['color']?.toString());
              final accentColor = partidoColor ?? const Color(0xFF1C4CA3);
              final surfaceColor = _mixWithWhite(accentColor, 0.93);
              return Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                  decoration: BoxDecoration(
                    color: surfaceColor,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: _mixWithWhite(accentColor, 0.72)),
                  ),
                  child: Row(
                    children: [
                    Container(
                      width: 5,
                      height: 34,
                      decoration: BoxDecoration(
                        color: accentColor,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                    const SizedBox(width: 8),
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
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            (p['sigla'] ?? '').toString(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: _mixWithBlack(accentColor, 0.18),
                            ),
                          ),
                          Text(
                            (p['nombre'] ?? '').toString(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: _mixWithBlack(accentColor, 0.3),
                            ),
                          ),
                        ],
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
                        onChanged: (_) => _enforceCategoryLimit(tab, voteMap[id]!),
                      ),
                    ),
                    ],
                  ),
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
                    onChanged: (_) => _enforceCategoryLimit(tab, blancosCtrl),
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
                    onChanged: (_) => _enforceCategoryLimit(tab, nulosCtrl),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (showPnu)
              TextField(
                controller: papeletasNoUtilizadasCtrl,
                enabled: editable,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  border: OutlineInputBorder(),
                  labelText: 'Papeletas no utilizadas',
                  isDense: true,
                ),
                onChanged: (_) => _enforceCategoryLimit(tab, papeletasNoUtilizadasCtrl),
              ),
            const SizedBox(height: 6),
            Text(
              'Total ${sum + _ival(blancosCtrl) + _ival(nulosCtrl) + (showPnu ? _ival(papeletasNoUtilizadasCtrl) : 0)}/$_targetTotalPorCategoria',
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

Color? _partidoColor(String? raw) {
  if (raw == null) return null;
  final hex = raw.trim();
  if (hex.isEmpty) return null;
  final normalized = hex.startsWith('#') ? hex.substring(1) : hex;
  if (normalized.length != 6 && normalized.length != 8) return null;
  final value = int.tryParse(normalized, radix: 16);
  if (value == null) return null;
  return Color(normalized.length == 6 ? 0xFF000000 | value : value);
}

Color _mixWithWhite(Color color, double amount) {
  return Color.lerp(color, Colors.white, amount.clamp(0, 1)) ?? color;
}

Color _mixWithBlack(Color color, double amount) {
  return Color.lerp(color, Colors.black, amount.clamp(0, 1)) ?? color;
}
