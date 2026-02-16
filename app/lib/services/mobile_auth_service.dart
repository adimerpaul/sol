import 'dart:convert';

import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:http/http.dart' as http;

import '../models/mobile_login_response.dart';

class MobileAuthService {
  String _buildUrl(String path) {
    final baseUrl = dotenv.env['API_BACK'] ?? '';
    if (baseUrl.isEmpty) {
      throw StateError('API_BACK no esta configurado en .env');
    }
    if (baseUrl.endsWith('/')) {
      return '$baseUrl$path';
    }
    return '$baseUrl/$path';
  }

  Future<MobileLoginResponse> login({
    required String ci,
    required String fechaNacimiento,
  }) async {
    final uri = Uri.parse(_buildUrl('login'));
    final response = await http.post(
      uri,
      headers: const {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'ci': ci, 'fecha_nacimiento': fechaNacimiento}),
    );

    final body = _decodeBody(response.body);

    if (response.statusCode != 200) {
      final message = body['message']?.toString() ?? 'Error de login';
      throw StateError(message);
    }

    return MobileLoginResponse.fromJson(body);
  }

  Map<String, dynamic> _decodeBody(String body) {
    if (body.isEmpty) return <String, dynamic>{};
    final decoded = jsonDecode(body);
    if (decoded is Map<String, dynamic>) return decoded;
    throw const FormatException('Respuesta JSON invalida para login');
  }
}
