class MobileLoginResponse {
  MobileLoginResponse({
    required this.token,
    required this.userName,
    required this.userCi,
    required this.userRole,
    required this.mesaNumero,
    required this.mesaEstado,
    required this.recintoNombre,
  });

  final String token;
  final String userName;
  final String userCi;
  final String? userRole;
  final String? mesaNumero;
  final String? mesaEstado;
  final String? recintoNombre;

  factory MobileLoginResponse.fromJson(Map<String, dynamic> json) {
    final user = (json['user'] as Map<String, dynamic>?) ?? {};
    final mesa = (json['mesa'] as Map<String, dynamic>?) ?? {};
    final recinto = (mesa['recinto'] as Map<String, dynamic>?) ?? {};

    return MobileLoginResponse(
      token: (json['token'] as String?) ?? '',
      userName: (user['name'] as String?) ?? '',
      userCi: (user['ci'] as String?) ?? '',
      userRole: user['role'] as String?,
      mesaNumero: mesa['numero_mesa']?.toString(),
      mesaEstado: mesa['estado']?.toString(),
      recintoNombre: recinto['nombre']?.toString(),
    );
  }
}
