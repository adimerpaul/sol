import 'package:sqflite/sqflite.dart';

import '../models/mobile_login_response.dart';

class MobileAuthLocalStore {
  MobileAuthLocalStore._internal();

  static final MobileAuthLocalStore instance = MobileAuthLocalStore._internal();
  static Database? _db;

  static const String _dbName = 'supervision.db';
  static const int _dbVersion = 1;

  static const String mesaEstadoPendiente = 'PENDIENTE';
  static const String mesaEstadoLocal = 'LOCAL';
  static const String mesaEstadoRealizado = 'REALIZADO';

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

    await _addColumnIfMissing(db, 'auth_session', 'user_celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_jefes', 'celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_supervisores', 'celular', 'TEXT');
    await _addColumnIfMissing(db, 'auth_mesas', 'recinto_id', 'INTEGER');
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
    final rows = await db.query(
      'auth_mesas',
      where: 'estado_local = ?',
      whereArgs: [mesaEstadoLocal],
      limit: 1,
    );
    return rows.isNotEmpty;
  }

  Future<void> clearSession() async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('auth_session');
      await txn.delete('auth_jefes');
      await txn.delete('auth_supervisores');
      await txn.delete('auth_jefe_supervisor');
      await txn.delete('auth_mesas');
    });
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
