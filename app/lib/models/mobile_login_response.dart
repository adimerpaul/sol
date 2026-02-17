class MobileLoginResponse {
  MobileLoginResponse({
    required this.token,
    required this.user,
    required this.jerarquia,
    required this.mesas,
  });

  final String token;
  final MobileUser user;
  final MobileJerarquia jerarquia;
  final List<MobileMesa> mesas;

  factory MobileLoginResponse.fromJson(Map<String, dynamic> json) {
    final userJson = (json['user'] as Map<String, dynamic>?) ?? {};
    final jerarquiaJson = (json['jerarquia'] as Map<String, dynamic>?) ?? {};
    final mesasJson = (json['mesas'] as List?) ?? const [];

    return MobileLoginResponse(
      token: (json['token'] as String?) ?? '',
      user: MobileUser.fromJson(userJson),
      jerarquia: MobileJerarquia.fromJson(jerarquiaJson),
      mesas: mesasJson
          .whereType<Map<String, dynamic>>()
          .map(MobileMesa.fromJson)
          .toList(),
    );
  }
}

class MobileUser {
  MobileUser({
    required this.id,
    required this.name,
    required this.ci,
    required this.fechaNacimiento,
    required this.role,
    required this.celular,
  });

  final int? id;
  final String name;
  final String ci;
  final String? fechaNacimiento;
  final String? role;
  final String? celular;

  factory MobileUser.fromJson(Map<String, dynamic> json) {
    return MobileUser(
      id: _asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      ci: (json['ci'] as String?) ?? '',
      fechaNacimiento: json['fecha_nacimiento'] as String?,
      role: json['role'] as String?,
      celular: json['celular'] as String?,
    );
  }
}

class MobileJerarquia {
  MobileJerarquia({required this.jefes, required this.supervisores});

  final List<MobilePersonaSimple> jefes;
  final List<MobilePersonaSimple> supervisores;

  factory MobileJerarquia.fromJson(Map<String, dynamic> json) {
    final jefesJson = (json['jefes'] as List?) ?? const [];
    final supervisoresJson = (json['supervisor'] as List?) ?? const [];

    return MobileJerarquia(
      jefes: jefesJson
          .whereType<Map<String, dynamic>>()
          .map(MobilePersonaSimple.fromJson)
          .toList(),
      supervisores: supervisoresJson
          .whereType<Map<String, dynamic>>()
          .map(MobilePersonaSimple.fromJson)
          .toList(),
    );
  }
}

class MobilePersonaSimple {
  MobilePersonaSimple({
    required this.id,
    required this.name,
    required this.nombres,
    required this.celular,
    required this.supervisores,
  });

  final int? id;
  final String name;
  final String? nombres;
  final String? celular;
  final List<MobilePersonaSimple> supervisores;

  factory MobilePersonaSimple.fromJson(Map<String, dynamic> json) {
    final supervisoresJson = (json['supervisores'] as List?) ?? const [];

    return MobilePersonaSimple(
      id: _asInt(json['id']),
      name: (json['name'] as String?) ?? '',
      nombres: json['nombres'] as String?,
      celular: json['celular'] as String?,
      supervisores: supervisoresJson
          .whereType<Map<String, dynamic>>()
          .map(MobilePersonaSimple.fromJson)
          .toList(),
    );
  }
}

class MobileMesa {
  MobileMesa({
    required this.id,
    required this.idOriginal,
    required this.recintoId,
    required this.numeroMesa,
    required this.estado,
    required this.estadoLocal,
    required this.recintoNombre,
    required this.localidadNombre,
    required this.municipioNombre,
    required this.provinciaNombre,
    required this.departamentoNombre,
    required this.recintoLatitud,
    required this.recintoLongitud,
  });

  final int? id;
  final int? idOriginal;
  final int? recintoId;
  final int? numeroMesa;
  final String? estado;
  final String? estadoLocal;
  final String? recintoNombre;
  final String? localidadNombre;
  final String? municipioNombre;
  final String? provinciaNombre;
  final String? departamentoNombre;
  final String? recintoLatitud;
  final String? recintoLongitud;

  factory MobileMesa.fromJson(Map<String, dynamic> json) {
    final recinto = (json['recinto'] as Map<String, dynamic>?) ?? {};
    final localidad = (json['localidad'] as Map<String, dynamic>?) ?? {};
    final municipio = (json['municipio'] as Map<String, dynamic>?) ?? {};
    final provincia = (json['provincia'] as Map<String, dynamic>?) ?? {};
    final departamento = (json['departamento'] as Map<String, dynamic>?) ?? {};

    return MobileMesa(
      id: _asInt(json['id']),
      idOriginal: _asInt(json['id_original']),
      recintoId: _asInt(json['recinto_id']),
      numeroMesa: _asInt(json['numero_mesa']),
      estado: json['estado']?.toString(),
      estadoLocal: json['estado_local']?.toString(),
      recintoNombre: recinto['nombre']?.toString(),
      localidadNombre: localidad['nombre']?.toString(),
      municipioNombre: municipio['nombre']?.toString(),
      provinciaNombre: provincia['nombre']?.toString(),
      departamentoNombre: departamento['nombre']?.toString(),
      recintoLatitud: recinto['latitud']?.toString(),
      recintoLongitud: recinto['longitud']?.toString(),
    );
  }
}

int? _asInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}
