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
    _db = await openDatabase(path, version: _dbVersion, onCreate: _onCreate);
    return _db!;
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
        updated_at TEXT NOT NULL
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_jefes (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE auth_supervisores (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        nombres TEXT
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
        'updated_at': now,
      });

      final supervisorIds = <int>{};

      for (final jefe in data.jerarquia.jefes) {
        if (jefe.id == null) continue;
        batch.insert('auth_jefes', {
          'id': jefe.id,
          'name': jefe.name,
          'nombres': jefe.nombres,
        }, conflictAlgorithm: ConflictAlgorithm.replace);

        for (final supervisor in jefe.supervisores) {
          if (supervisor.id == null) continue;
          supervisorIds.add(supervisor.id!);
          batch.insert('auth_supervisores', {
            'id': supervisor.id,
            'name': supervisor.name,
            'nombres': supervisor.nombres,
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
          }, conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }

      for (final mesa in data.mesas) {
        if (mesa.id == null) continue;
        batch.insert('auth_mesas', {
          'id': mesa.id,
          'id_original': mesa.idOriginal,
          'numero_mesa': mesa.numeroMesa,
          'estado_api': mesa.estado,
          'estado_local': _estadoLocalInicial(mesa.estado),
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
          supervisores: jefeSupervisores,
        ),
      );
    }

    final mesas = mesasRows
        .map(
          (row) => MobileMesa(
            id: row['id'] as int?,
            idOriginal: row['id_original'] as int?,
            numeroMesa: row['numero_mesa'] as int?,
            estado: row['estado_api'] as String?,
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
      ),
      jerarquia: MobileJerarquia(
        jefes: jefes,
        supervisores: supervisorsById.values.toList(),
      ),
      mesas: mesas,
    );
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
