import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import '../models/mobile_login_response.dart';
import '../models/votacion_model.dart';

class MobileAuthLocalStore {
  MobileAuthLocalStore._internal();

  static final MobileAuthLocalStore instance = MobileAuthLocalStore._internal();
  static Database? _db;

  static const String _dbName = 'supervision.db';
  static const int _dbVersion = 1;

  static const String mesaEstadoPendiente = 'PENDIENTE';
  static const String mesaEstadoLocal = 'LOCAL';
  static const String mesaEstadoRealizado = 'REALIZADO';
  static const String asistenciaSyncLocal = 'LOCAL';
  static const String asistenciaSyncSynced = 'SYNCED';
  static const String votacionSyncLocal = 'LOCAL';
  static const String votacionSyncError = 'ERROR';
  static const String votacionSyncSynced = 'SYNCED';

  Future<Database> get database async {
    if (_db != null) return _db!;
    final path = '${await getDatabasesPath()}/$_dbName';
    _db = await openDatabase(
      path,
      version: _dbVersion,
      onCreate: _onCreate,
      onOpen: _onOpen,
    );
    return _db!;
  }

  Future<void> _onOpen(Database db) async {
    await _ensureSchema(db);
  }

  Future<void> _onCreate(Database db, int version) async {
    await db.execute('''
      CREATE TABLE auth_session (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        token TEXT NOT NULL,
        user_id INTEGER,
        user_name TEXT NOT NULL,
        user_ci TEXT NOT NULL,
        user_fecha_nacimiento TEXT,
        user_role TEXT,
        user_celular TEXT,
        updated_at TEXT NOT NULL
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_jefes (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT,
        celular TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_supervisores (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT,
        celular TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE auth_partidos (
        id INTEGER PRIMARY KEY,
        sigla TEXT NOT NULL,
        nombre TEXT NOT NULL,
        icono TEXT,
        icono_url TEXT,
        icono_base64 TEXT,
        orden_municipal INTEGER NOT NULL DEFAULT 0,
        orden_departamental INTEGER NOT NULL DEFAULT 0
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_jefe_supervisor (
        jefe_id INTEGER NOT NULL,
        supervisor_id INTEGER NOT NULL,
        PRIMARY KEY (jefe_id, supervisor_id)
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_mesas (
        id INTEGER PRIMARY KEY,
        id_original INTEGER,
        recinto_id INTEGER,
        numero_mesa INTEGER,
        estado_api TEXT,
        estado_local TEXT NOT NULL,
        recinto_nombre TEXT,
        recinto_latitud TEXT,
        recinto_longitud TEXT,
        localidad_nombre TEXT,
        municipio_nombre TEXT,
        provincia_nombre TEXT,
        departamento_nombre TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE asistencia_state (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        aviso_antes INTEGER NOT NULL DEFAULT 0,
        aviso_manana INTEGER NOT NULL DEFAULT 0,
        aviso_mediodia INTEGER NOT NULL DEFAULT 0,
        hora_apertura_mesa TEXT,
        aviso_tarde INTEGER NOT NULL DEFAULT 0,
        etapa_1 INTEGER NOT NULL DEFAULT 0,
        etapa_2 INTEGER NOT NULL DEFAULT 0,
        sync_status TEXT NOT NULL DEFAULT 'SYNCED',
        updated_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE asistencia_queue (
        field TEXT PRIMARY KEY,
        value INTEGER NOT NULL,
        hora_apertura_mesa TEXT,
        updated_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE votacion_draft (
        mesa_id INTEGER PRIMARY KEY,
        payload_json TEXT NOT NULL,
        fotos_json TEXT,
        sync_status TEXT NOT NULL DEFAULT 'LOCAL',
        last_error TEXT,
        updated_at TEXT NOT NULL
      )
    ''');

    await _ensureSchema(db);
  }

  Future<void> _ensureSchema(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_session (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        token TEXT NOT NULL,
        user_id INTEGER,
        user_name TEXT NOT NULL,
        user_ci TEXT NOT NULL,
        user_fecha_nacimiento TEXT,
        user_role TEXT,
        user_celular TEXT,
        updated_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_jefes (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT,
        celular TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_supervisores (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT,
        celular TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_partidos (
        id INTEGER PRIMARY KEY,
        sigla TEXT NOT NULL,
        nombre TEXT NOT NULL,
        icono TEXT,
        icono_url TEXT,
        icono_base64 TEXT,
        orden_municipal INTEGER NOT NULL DEFAULT 0,
        orden_departamental INTEGER NOT NULL DEFAULT 0
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_jefe_supervisor (
        jefe_id INTEGER NOT NULL,
        supervisor_id INTEGER NOT NULL,
        PRIMARY KEY (jefe_id, supervisor_id)
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS auth_mesas (
        id INTEGER PRIMARY KEY,
        id_original INTEGER,
        recinto_id INTEGER,
        numero_mesa INTEGER,
        estado_api TEXT,
        estado_local TEXT NOT NULL,
        recinto_nombre TEXT,
        recinto_latitud TEXT,
        recinto_longitud TEXT,
        localidad_nombre TEXT,
        municipio_nombre TEXT,
        provincia_nombre TEXT,
        departamento_nombre TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS asistencia_state (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        aviso_antes INTEGER NOT NULL DEFAULT 0,
        aviso_manana INTEGER NOT NULL DEFAULT 0,
        aviso_mediodia INTEGER NOT NULL DEFAULT 0,
        hora_apertura_mesa TEXT,
        aviso_tarde INTEGER NOT NULL DEFAULT 0,
        etapa_1 INTEGER NOT NULL DEFAULT 0,
        etapa_2 INTEGER NOT NULL DEFAULT 0,
        sync_status TEXT NOT NULL DEFAULT 'SYNCED',
        updated_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS asistencia_queue (
        field TEXT PRIMARY KEY,
        value INTEGER NOT NULL,
        hora_apertura_mesa TEXT,
        updated_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS votacion_draft (
        mesa_id INTEGER PRIMARY KEY,
        payload_json TEXT NOT NULL,
        fotos_json TEXT,
        sync_status TEXT NOT NULL DEFAULT 'LOCAL',
        last_error TEXT,
        updated_at TEXT NOT NULL
      )
    ''');

    await _addColumnIfMissing(db, 'auth_session', 'user_celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_jefes', 'celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_supervisores', 'celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_mesas', 'recinto_id', 'INTEGER');
    await _addColumnIfMissing(db, 'votacion_draft', 'fotos_json', 'TEXT');
    await _addColumnIfMissing(db, 'auth_partidos', 'icono_base64', 'TEXT');
    await _addColumnIfMissing(db, 'asistencia_state', 'hora_apertura_mesa', 'TEXT');
    await _addColumnIfMissing(db, 'asistencia_queue', 'hora_apertura_mesa', 'TEXT');
  }

  Future<void> _addColumnIfMissing(
    Database db,
    String table,
    String column,
    String definition,
  ) async {
    final hasColumn = await _hasColumn(db, table, column);
    if (!hasColumn) {
      await db.execute('ALTER TABLE $table ADD COLUMN $column $definition');
    }
  }

  Future<bool> _hasColumn(Database db, String table, String column) async {
    final result = await db.rawQuery('PRAGMA table_info($table)');
    for (final row in result) {
      if ((row['name'] as String?) == column) {
        return true;
      }
    }
    return false;
  }

  Future<void> saveLogin(MobileLoginResponse data) async {
    final db = await database;
    await db.transaction((txn) async {
      final batch = txn.batch();
      final now = DateTime.now().toIso8601String();

      batch.delete('auth_session');
      batch.delete('auth_jefes');
      batch.delete('auth_supervisores');
      batch.delete('auth_jefe_supervisor');
      batch.delete('auth_mesas');
      batch.delete('auth_partidos');

      batch.insert('auth_session', {
        'id': 1,
        'token': data.token,
        'user_id': data.user.id,
        'user_name': data.user.name,
        'user_ci': data.user.ci,
        'user_fecha_nacimiento': data.user.fechaNacimiento,
        'user_role': data.user.role,
        'user_celular': data.user.celular,
        'updated_at': now,
      });

      final supervisorIds = <int>{};

      for (final jefe in data.jerarquia.jefes) {
        if (jefe.id == null) continue;
        batch.insert('auth_jefes', {
          'id': jefe.id,
          'name': jefe.name,
          'nombres': jefe.nombres,
          'celular': jefe.celular,
        }, conflictAlgorithm: ConflictAlgorithm.replace);

        for (final supervisor in jefe.supervisores) {
          if (supervisor.id == null) continue;
          supervisorIds.add(supervisor.id!);
          batch.insert('auth_supervisores', {
            'id': supervisor.id,
            'name': supervisor.name,
            'nombres': supervisor.nombres,
            'celular': supervisor.celular,
          }, conflictAlgorithm: ConflictAlgorithm.replace);
          batch.insert('auth_jefe_supervisor', {
            'jefe_id': jefe.id,
            'supervisor_id': supervisor.id,
          }, conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }

      for (final supervisor in data.jerarquia.supervisores) {
        if (supervisor.id == null) continue;
        if (!supervisorIds.contains(supervisor.id)) {
          batch.insert('auth_supervisores', {
            'id': supervisor.id,
            'name': supervisor.name,
            'nombres': supervisor.nombres,
            'celular': supervisor.celular,
          }, conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }

      for (final mesa in data.mesas) {
        if (mesa.id == null) continue;
        batch.insert('auth_mesas', {
          'id': mesa.id,
          'id_original': mesa.idOriginal,
          'recinto_id': mesa.recintoId,
          'numero_mesa': mesa.numeroMesa,
          'estado_api': mesa.estado,
          'estado_local': mesa.estadoLocal ?? _estadoLocalInicial(mesa.estado),
          'recinto_nombre': mesa.recintoNombre,
          'recinto_latitud': mesa.recintoLatitud,
          'recinto_longitud': mesa.recintoLongitud,
          'localidad_nombre': mesa.localidadNombre,
          'municipio_nombre': mesa.municipioNombre,
          'provincia_nombre': mesa.provinciaNombre,
          'departamento_nombre': mesa.departamentoNombre,
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }

      for (final partido in data.partidos) {
        batch.insert('auth_partidos', {
          'id': partido.id,
          'sigla': partido.sigla,
          'nombre': partido.nombre,
          'icono': partido.icono,
          'icono_url': partido.iconoUrl,
          'icono_base64': partido.iconoBase64,
          'orden_municipal': partido.ordenMunicipal,
          'orden_departamental': partido.ordenDepartamental,
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }

      await batch.commit(noResult: true);
    });
  }

  Future<MobileLoginResponse?> readLoginForUser({
    required String ci,
    required String fechaNacimiento,
  }) async {
    final db = await database;
    final sessionRows = await db.query(
      'auth_session',
      where: 'id = 1 AND user_ci = ? AND user_fecha_nacimiento = ?',
      whereArgs: [ci, fechaNacimiento],
      limit: 1,
    );
    if (sessionRows.isEmpty) return null;

    final session = sessionRows.first;
    final jefesRows = await db.query('auth_jefes');
    final supervisorRows = await db.query('auth_supervisores');
    final jefeSupervisorRows = await db.query('auth_jefe_supervisor');
    final mesasRows = await db.query('auth_mesas');
    final partidosRows = await db.query(
      'auth_partidos',
      orderBy: 'orden_municipal ASC, sigla ASC',
    );

    final supervisorsById = <int, MobilePersonaSimple>{};
    for (final row in supervisorRows) {
      final id = row['id'] as int?;
      if (id == null) continue;
      supervisorsById[id] = MobilePersonaSimple(
        id: id,
        name: (row['name'] as String?) ?? '',
        nombres: row['nombres'] as String?,
        celular: row['celular'] as String?,
        supervisores: const [],
      );
    }

    final supervisorIdsByJefe = <int, List<int>>{};
    for (final row in jefeSupervisorRows) {
      final jefeId = row['jefe_id'] as int?;
      final supervisorId = row['supervisor_id'] as int?;
      if (jefeId == null || supervisorId == null) continue;
      supervisorIdsByJefe.putIfAbsent(jefeId, () => []).add(supervisorId);
    }

    final jefes = <MobilePersonaSimple>[];
    for (final row in jefesRows) {
      final jefeId = row['id'] as int?;
      if (jefeId == null) continue;
      final jefeSupervisorIds = supervisorIdsByJefe[jefeId] ?? const [];
      final jefeSupervisores = jefeSupervisorIds
          .map((id) => supervisorsById[id])
          .whereType<MobilePersonaSimple>()
          .toList();

      jefes.add(
        MobilePersonaSimple(
          id: jefeId,
          name: (row['name'] as String?) ?? '',
          nombres: row['nombres'] as String?,
          celular: row['celular'] as String?,
          supervisores: jefeSupervisores,
        ),
      );
    }

    final mesas = mesasRows
        .map(
          (row) => MobileMesa(
            id: row['id'] as int?,
            idOriginal: row['id_original'] as int?,
            recintoId: row['recinto_id'] as int?,
            numeroMesa: row['numero_mesa'] as int?,
            estado: row['estado_api'] as String?,
            estadoLocal: row['estado_local'] as String?,
            recintoNombre: row['recinto_nombre'] as String?,
            localidadNombre: row['localidad_nombre'] as String?,
            municipioNombre: row['municipio_nombre'] as String?,
            provinciaNombre: row['provincia_nombre'] as String?,
            departamentoNombre: row['departamento_nombre'] as String?,
            recintoLatitud: row['recinto_latitud'] as String?,
            recintoLongitud: row['recinto_longitud'] as String?,
          ),
        )
        .toList();

    final partidos = partidosRows
        .map(
          (row) => MobilePartido(
            id: row['id'] as int? ?? 0,
            sigla: (row['sigla'] as String?) ?? '',
            nombre: (row['nombre'] as String?) ?? '',
            icono: row['icono'] as String?,
            iconoUrl: row['icono_url'] as String?,
            iconoBase64: row['icono_base64'] as String?,
            ordenMunicipal: row['orden_municipal'] as int? ?? 0,
            ordenDepartamental: row['orden_departamental'] as int? ?? 0,
          ),
        )
        .toList();

    return MobileLoginResponse(
      token: (session['token'] as String?) ?? '',
      user: MobileUser(
        id: session['user_id'] as int?,
        name: (session['user_name'] as String?) ?? '',
        ci: (session['user_ci'] as String?) ?? '',
        fechaNacimiento: session['user_fecha_nacimiento'] as String?,
        role: session['user_role'] as String?,
        celular: session['user_celular'] as String?,
      ),
      jerarquia: MobileJerarquia(
        jefes: jefes,
        supervisores: supervisorsById.values.toList(),
      ),
      mesas: mesas,
      partidos: partidos,
    );
  }

  Future<MobileProfileData?> readProfileData() async {
    final db = await database;
    final sessionRows = await db.query(
      'auth_session',
      where: 'id = 1',
      limit: 1,
    );
    if (sessionRows.isEmpty) return null;
    final session = sessionRows.first;
    final jefesRows = await db.query('auth_jefes');
    final supervisoresRows = await db.query('auth_supervisores');

    final user = MobileUser(
      id: session['user_id'] as int?,
      name: (session['user_name'] as String?) ?? '',
      ci: (session['user_ci'] as String?) ?? '',
      fechaNacimiento: session['user_fecha_nacimiento'] as String?,
      role: session['user_role'] as String?,
      celular: session['user_celular'] as String?,
    );

    final jefes = jefesRows
        .map(
          (row) => MobilePersonaSimple(
            id: row['id'] as int?,
            name: (row['name'] as String?) ?? '',
            nombres: row['nombres'] as String?,
            celular: row['celular'] as String?,
            supervisores: const [],
          ),
        )
        .toList();

    final supervisores = supervisoresRows
        .map(
          (row) => MobilePersonaSimple(
            id: row['id'] as int?,
            name: (row['name'] as String?) ?? '',
            nombres: row['nombres'] as String?,
            celular: row['celular'] as String?,
            supervisores: const [],
          ),
        )
        .toList();

    return MobileProfileData(
      user: user,
      jefes: jefes,
      supervisores: supervisores,
    );
  }

  Future<List<RecintoMesaPoint>> getRecintosDesdeMesas() async {
    final db = await database;
    final rows = await db.query('auth_mesas');

    final grouped = <String, RecintoMesaPoint>{};
    for (final row in rows) {
      final latText = (row['recinto_latitud'] as String?)?.trim();
      final lngText = (row['recinto_longitud'] as String?)?.trim();
      if (latText == null ||
          lngText == null ||
          latText.isEmpty ||
          lngText.isEmpty) {
        continue;
      }

      final lat = double.tryParse(latText);
      final lng = double.tryParse(lngText);
      if (lat == null || lng == null) continue;

      final recintoId = row['recinto_id'] as int?;
      final recintoNombre = (row['recinto_nombre'] as String?) ?? 'Recinto';
      final key = recintoId != null
          ? 'id:$recintoId'
          : '$recintoNombre|$lat|$lng';
      final estadoLocal =
          ((row['estado_local'] as String?) ?? mesaEstadoPendiente)
              .toUpperCase();

      final existing = grouped[key];
      if (existing == null) {
        grouped[key] = RecintoMesaPoint(
          recintoId: recintoId,
          recintoNombre: recintoNombre,
          latitud: lat,
          longitud: lng,
          totalMesas: 1,
          pendientes: estadoLocal == mesaEstadoPendiente ? 1 : 0,
          locales: estadoLocal == mesaEstadoLocal ? 1 : 0,
          realizados: estadoLocal == mesaEstadoRealizado ? 1 : 0,
        );
      } else {
        grouped[key] = existing.copyWith(
          totalMesas: existing.totalMesas + 1,
          pendientes:
              existing.pendientes +
              (estadoLocal == mesaEstadoPendiente ? 1 : 0),
          locales: existing.locales + (estadoLocal == mesaEstadoLocal ? 1 : 0),
          realizados:
              existing.realizados +
              (estadoLocal == mesaEstadoRealizado ? 1 : 0),
        );
      }
    }

    final points = grouped.values.toList();
    points.sort((a, b) => a.recintoNombre.compareTo(b.recintoNombre));
    return points;
  }

  Future<void> updateMesaEstadoLocal(int mesaId, String estadoLocal) async {
    if (!_isValidEstadoLocal(estadoLocal)) {
      throw ArgumentError('Estado local invalido: $estadoLocal');
    }
    final db = await database;
    await db.update(
      'auth_mesas',
      {'estado_local': estadoLocal},
      where: 'id = ?',
      whereArgs: [mesaId],
    );
  }

  Future<bool> hasSession() async {
    final db = await database;
    final rows = await db.query('auth_session', limit: 1);
    return rows.isNotEmpty;
  }

  Future<bool> hasMesasLocalPendientesSync() async {
    final db = await database;
    final rowsMesas = await db.query(
      'auth_mesas',
      where: 'estado_local = ?',
      whereArgs: [mesaEstadoLocal],
      limit: 1,
    );
    if (rowsMesas.isNotEmpty) return true;
    final rowsAsistencia = await db.query('asistencia_queue', limit: 1);
    if (rowsAsistencia.isNotEmpty) return true;
    final rowsVotacion = await db.query(
      'votacion_draft',
      where: 'sync_status != ?',
      whereArgs: [votacionSyncSynced],
      limit: 1,
    );
    return rowsVotacion.isNotEmpty;
  }

  Future<int> getPendingSyncCount() async {
    final db = await database;
    final rowsMesas = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM auth_mesas WHERE estado_local = ?',
      [mesaEstadoLocal],
    );
    final rowsAsistencia = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM asistencia_queue',
    );
    final rowsVotacion = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM votacion_draft WHERE sync_status != ?',
      [votacionSyncSynced],
    );
    final cMesas = _asInt(rowsMesas.first['c']) ?? 0;
    final cAsistencia = _asInt(rowsAsistencia.first['c']) ?? 0;
    final cVotacion = _asInt(rowsVotacion.first['c']) ?? 0;
    return cMesas + cAsistencia + cVotacion;
  }

  Future<void> clearSession() async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('auth_session');
      await txn.delete('auth_jefes');
      await txn.delete('auth_supervisores');
      await txn.delete('auth_jefe_supervisor');
      await txn.delete('auth_mesas');
      await txn.delete('auth_partidos');
      await txn.delete('asistencia_state');
      await txn.delete('asistencia_queue');
      await txn.delete('votacion_draft');
    });
  }

  Future<String?> readAuthToken() async {
    final db = await database;
    final rows = await db.query('auth_session', where: 'id = 1', limit: 1);
    if (rows.isEmpty) return null;
    return rows.first['token'] as String?;
  }

  Future<void> saveAsistenciaState({
    required bool avisoAntes,
    required bool avisoManana,
    required bool avisoMediodia,
    String? horaAperturaMesa,
    required bool avisoTarde,
    required bool etapa1,
    required bool etapa2,
    required String syncStatus,
  }) async {
    final db = await database;
    await db.insert('asistencia_state', {
      'id': 1,
      'aviso_antes': avisoAntes ? 1 : 0,
      'aviso_manana': avisoManana ? 1 : 0,
      'aviso_mediodia': avisoMediodia ? 1 : 0,
      'hora_apertura_mesa': horaAperturaMesa,
      'aviso_tarde': avisoTarde ? 1 : 0,
      'etapa_1': etapa1 ? 1 : 0,
      'etapa_2': etapa2 ? 1 : 0,
      'sync_status': syncStatus,
      'updated_at': DateTime.now().toIso8601String(),
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<Map<String, dynamic>> readAsistenciaState() async {
    final db = await database;
    final rows = await db.query('asistencia_state', where: 'id = 1', limit: 1);
    if (rows.isEmpty) {
      return {
        'aviso_antes': false,
        'aviso_manana': false,
        'aviso_mediodia': false,
        'hora_apertura_mesa': null,
        'aviso_tarde': false,
        'etapa_1': false,
        'etapa_2': false,
        'sync_status': asistenciaSyncSynced,
      };
    }
    final r = rows.first;
    return {
      'aviso_antes': (r['aviso_antes'] as int? ?? 0) == 1,
      'aviso_manana': (r['aviso_manana'] as int? ?? 0) == 1,
      'aviso_mediodia': (r['aviso_mediodia'] as int? ?? 0) == 1,
      'hora_apertura_mesa': r['hora_apertura_mesa'] as String?,
      'aviso_tarde': (r['aviso_tarde'] as int? ?? 0) == 1,
      'etapa_1': (r['etapa_1'] as int? ?? 0) == 1,
      'etapa_2': (r['etapa_2'] as int? ?? 0) == 1,
      'sync_status': (r['sync_status'] as String?) ?? asistenciaSyncSynced,
    };
  }

  Future<void> enqueueAsistenciaChange(
    String field,
    bool value, {
    String? horaAperturaMesa,
  }) async {
    final db = await database;
    await db.insert('asistencia_queue', {
      'field': field,
      'value': value ? 1 : 0,
      'hora_apertura_mesa': horaAperturaMesa,
      'updated_at': DateTime.now().toIso8601String(),
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<void> dequeueAsistenciaField(String field) async {
    final db = await database;
    await db.delete('asistencia_queue', where: 'field = ?', whereArgs: [field]);
  }

  Future<List<Map<String, dynamic>>> readAsistenciaQueue() async {
    final db = await database;
    final rows = await db.query('asistencia_queue', orderBy: 'updated_at ASC');
    return rows
        .map(
          (r) => {
            'field': r['field'],
            'value': (r['value'] as int? ?? 0) == 1,
            'hora_apertura_mesa': r['hora_apertura_mesa'],
          },
        )
        .toList();
  }

  Future<bool> hasAsistenciaPendiente() async {
    final db = await database;
    final rows = await db.query('asistencia_queue', limit: 1);
    return rows.isNotEmpty;
  }

  Future<List<MobileMesa>> readMesasLocal() async {
    final db = await database;
    final rows = await db.query('auth_mesas', orderBy: 'numero_mesa ASC');
    return rows
        .map(
          (row) => MobileMesa(
            id: row['id'] as int?,
            idOriginal: row['id_original'] as int?,
            recintoId: row['recinto_id'] as int?,
            numeroMesa: row['numero_mesa'] as int?,
            estado: row['estado_api'] as String?,
            estadoLocal: row['estado_local'] as String?,
            recintoNombre: row['recinto_nombre'] as String?,
            localidadNombre: row['localidad_nombre'] as String?,
            municipioNombre: row['municipio_nombre'] as String?,
            provinciaNombre: row['provincia_nombre'] as String?,
            departamentoNombre: row['departamento_nombre'] as String?,
            recintoLatitud: row['recinto_latitud'] as String?,
            recintoLongitud: row['recinto_longitud'] as String?,
          ),
        )
        .toList();
  }

  Future<List<MobilePartido>> readPartidosLocal() async {
    final db = await database;
    final rows = await db.query(
      'auth_partidos',
      orderBy: 'orden_municipal ASC, sigla ASC',
    );
    return rows
        .map(
          (row) => MobilePartido(
            id: row['id'] as int? ?? 0,
            sigla: (row['sigla'] as String?) ?? '',
            nombre: (row['nombre'] as String?) ?? '',
            icono: row['icono'] as String?,
            iconoUrl: row['icono_url'] as String?,
            iconoBase64: row['icono_base64'] as String?,
            ordenMunicipal: row['orden_municipal'] as int? ?? 0,
            ordenDepartamental: row['orden_departamental'] as int? ?? 0,
          ),
        )
        .toList();
  }

  Future<void> saveVotacionDraft(
    VotacionDraft draft, {
    bool markMesaLocal = true,
  }) async {
    final db = await database;
    final payload = {
      'finalizar': draft.finalizar,
      'observacion': draft.observacion,
      'blancos_gobernador': draft.blancosGobernador,
      'nulos_gobernador': draft.nulosGobernador,
      'blancos_asambleista_distrito': draft.blancosAsd,
      'nulos_asambleista_distrito': draft.nulosAsd,
      'blancos_asambleista_poblacion': draft.blancosAsp,
      'nulos_asambleista_poblacion': draft.nulosAsp,
      'blancos_concejal': draft.blancosConcejal,
      'nulos_concejal': draft.nulosConcejal,
      'papeletas_no_utilizadas_concejal': draft.papeletasNoUtilizadasConcejal,
      'blancos_alcalde': draft.blancosAlcalde,
      'nulos_alcalde': draft.nulosAlcalde,
      'papeletas_no_utilizadas_alcalde': draft.papeletasNoUtilizadasAlcalde,
      'votos': draft.votos
          .map(
            (v) => {
              'partido_id': v.partidoId,
              'sigla': v.sigla,
              'nombre': v.nombre,
              'icono_url': v.iconoUrl,
              'votos_gobernador': v.votosGobernador,
              'votos_asambleista_distrito': v.votosAsd,
              'votos_asambleista_poblacion': v.votosAsp,
              'votos_concejal': v.votosConcejal,
              'votos_alcalde': v.votosAlcalde,
            },
          )
          .toList(),
    };
    await db.insert('votacion_draft', {
      'mesa_id': draft.mesaId,
      'payload_json': jsonEncode(payload),
      'fotos_json': jsonEncode(draft.fotos),
      'sync_status': draft.syncStatus,
      'last_error': null,
      'updated_at': draft.updatedAt,
    }, conflictAlgorithm: ConflictAlgorithm.replace);

    if (markMesaLocal) {
      await updateMesaEstadoLocal(draft.mesaId, mesaEstadoLocal);
    }
  }

  Future<VotacionDraft?> readVotacionDraft(int mesaId) async {
    final db = await database;
    final rows = await db.query(
      'votacion_draft',
      where: 'mesa_id = ?',
      whereArgs: [mesaId],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    final row = rows.first;
    final raw = (row['payload_json'] as String?) ?? '{}';
    Map<String, dynamic> payload = {};
    try {
      payload = Map<String, dynamic>.from(jsonDecode(raw));
    } catch (_) {}
    Map<String, dynamic> fotosRaw = {};
    final rawFotos = row['fotos_json'] as String?;
    if (rawFotos?.isNotEmpty == true) {
      try {
        fotosRaw = Map<String, dynamic>.from(jsonDecode(rawFotos!));
      } catch (_) {}
    }
    final fotos = <String, String?>{};
    for (final f in votacionFotoSlots) {
      final v = fotosRaw[f];
      fotos[f] = v?.toString();
    }
    if ((fotos['foto1'] ?? '').isEmpty) {
      final legacy = row['hoja_trabajo_path'] as String?;
      if (legacy?.isNotEmpty == true) fotos['foto1'] = legacy;
    }
    if ((fotos['foto2'] ?? '').isEmpty) {
      final legacy = row['acta_electoral_path'] as String?;
      if (legacy?.isNotEmpty == true) fotos['foto2'] = legacy;
    }
    final votosRaw = (payload['votos'] as List?) ?? const [];
    final votos = votosRaw.whereType<Map>().map((e) {
      final m = Map<String, dynamic>.from(e);
      return VotoPartidoItem(
        partidoId: _asInt(m['partido_id']) ?? 0,
        sigla: (m['sigla'] ?? '').toString(),
        nombre: (m['nombre'] ?? '').toString(),
        iconoUrl: m['icono_url']?.toString(),
        votosGobernador: _asInt(m['votos_gobernador']) ?? 0,
        votosAsd: _asInt(m['votos_asambleista_distrito']) ?? 0,
        votosAsp: _asInt(m['votos_asambleista_poblacion']) ?? 0,
        votosConcejal: _asInt(m['votos_concejal']) ?? 0,
        votosAlcalde: _asInt(m['votos_alcalde']) ?? 0,
      );
    }).toList();
    return VotacionDraft(
      mesaId: mesaId,
      finalizar: payload['finalizar'] == true,
      observacion: payload['observacion']?.toString(),
      blancosGobernador: _asInt(payload['blancos_gobernador']) ?? 0,
      nulosGobernador: _asInt(payload['nulos_gobernador']) ?? 0,
      blancosAsd: _asInt(payload['blancos_asambleista_distrito']) ?? 0,
      nulosAsd: _asInt(payload['nulos_asambleista_distrito']) ?? 0,
      blancosAsp: _asInt(payload['blancos_asambleista_poblacion']) ?? 0,
      nulosAsp: _asInt(payload['nulos_asambleista_poblacion']) ?? 0,
      blancosConcejal: _asInt(payload['blancos_concejal']) ?? 0,
      nulosConcejal: _asInt(payload['nulos_concejal']) ?? 0,
      papeletasNoUtilizadasConcejal:
          _asInt(payload['papeletas_no_utilizadas_concejal']) ?? 0,
      blancosAlcalde: _asInt(payload['blancos_alcalde']) ?? 0,
      nulosAlcalde: _asInt(payload['nulos_alcalde']) ?? 0,
      papeletasNoUtilizadasAlcalde:
          _asInt(payload['papeletas_no_utilizadas_alcalde']) ?? 0,
      votos: votos,
      fotos: fotos,
      syncStatus: (row['sync_status'] as String?) ?? votacionSyncLocal,
      updatedAt: (row['updated_at'] as String?) ?? '',
    );
  }

  Future<List<VotacionDraft>> readPendingVotacionDrafts() async {
    final db = await database;
    final rows = await db.query(
      'votacion_draft',
      where: 'sync_status != ?',
      whereArgs: [votacionSyncSynced],
      orderBy: 'updated_at ASC',
    );
    final out = <VotacionDraft>[];
    for (final row in rows) {
      final mesaId = row['mesa_id'] as int?;
      if (mesaId == null) continue;
      final d = await readVotacionDraft(mesaId);
      if (d != null) out.add(d);
    }
    return out;
  }

  Future<void> markVotacionSynced(int mesaId) async {
    final db = await database;
    await db.update(
      'votacion_draft',
      {
        'sync_status': votacionSyncSynced,
        'last_error': null,
        'updated_at': DateTime.now().toIso8601String(),
      },
      where: 'mesa_id = ?',
      whereArgs: [mesaId],
    );
    await updateMesaEstadoLocal(mesaId, mesaEstadoRealizado);
  }

  Future<void> markVotacionError(int mesaId, String error) async {
    final db = await database;
    await db.update(
      'votacion_draft',
      {
        'sync_status': votacionSyncError,
        'last_error': error,
        'updated_at': DateTime.now().toIso8601String(),
      },
      where: 'mesa_id = ?',
      whereArgs: [mesaId],
    );
    await updateMesaEstadoLocal(mesaId, mesaEstadoLocal);
  }

  String _estadoLocalInicial(String? estadoApi) {
    if (estadoApi?.toUpperCase() == mesaEstadoRealizado) {
      return mesaEstadoRealizado;
    }
    return mesaEstadoPendiente;
  }

  bool _isValidEstadoLocal(String value) {
    final normalized = value.toUpperCase();
    return normalized == mesaEstadoPendiente ||
        normalized == mesaEstadoLocal ||
        normalized == mesaEstadoRealizado;
  }
}

class MobileProfileData {
  MobileProfileData({
    required this.user,
    required this.jefes,
    required this.supervisores,
  });

  final MobileUser user;
  final List<MobilePersonaSimple> jefes;
  final List<MobilePersonaSimple> supervisores;
}

class RecintoMesaPoint {
  RecintoMesaPoint({
    required this.recintoId,
    required this.recintoNombre,
    required this.latitud,
    required this.longitud,
    required this.totalMesas,
    required this.pendientes,
    required this.locales,
    required this.realizados,
  });

  final int? recintoId;
  final String recintoNombre;
  final double latitud;
  final double longitud;
  final int totalMesas;
  final int pendientes;
  final int locales;
  final int realizados;

  RecintoMesaPoint copyWith({
    int? totalMesas,
    int? pendientes,
    int? locales,
    int? realizados,
  }) {
    return RecintoMesaPoint(
      recintoId: recintoId,
      recintoNombre: recintoNombre,
      latitud: latitud,
      longitud: longitud,
      totalMesas: totalMesas ?? this.totalMesas,
      pendientes: pendientes ?? this.pendientes,
      locales: locales ?? this.locales,
      realizados: realizados ?? this.realizados,
    );
  }
}

int? _asInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}
