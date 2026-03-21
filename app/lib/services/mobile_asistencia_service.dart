import 'dart:convert';

import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:http/http.dart' as http;

import 'mobile_auth_local_store.dart';

class MobileAsistenciaService {
  MobileAsistenciaService({
    MobileAuthLocalStore? localStore,
    http.Client? client,
  }) : _localStore = localStore ?? MobileAuthLocalStore.instance,
       _client = client ?? http.Client();

  final MobileAuthLocalStore _localStore;
  final http.Client _client;

  String _buildUrl(String path) {
    final baseUrl = (dotenv.env['API_BACK'] ?? '').replaceAll('"', '').trim();
    if (baseUrl.isEmpty) {
      throw StateError('API_BACK no esta configurado');
    }
    if (baseUrl.endsWith('/')) return '$baseUrl$path';
    return '$baseUrl/$path';
  }

  Future<Map<String, dynamic>> fetchAsistenciaState() async {
    final token = await _localStore.readAuthToken();
    if (token == null || token.isEmpty) {
      throw StateError('Sin token');
    }
    final uri = Uri.parse(_buildUrl('asistencia'));
    final res = await _client
        .get(
          uri,
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
          },
        )
        .timeout(const Duration(seconds: 8));
    if (res.statusCode != 200) {
      throw StateError('No se pudo obtener asistencia');
    }
    final body = jsonDecode(res.body);
    if (body is! Map<String, dynamic>) {
      throw const FormatException('Respuesta invalida');
    }
    return body;
  }

  Future<void> sendAsistenciaToggle({
    required String field,
    required bool value,
    String? horaAperturaMesa,
    double? latitud,
    double? longitud,
    String? presenteAt,
  }) async {
    final token = await _localStore.readAuthToken();
    if (token == null || token.isEmpty) {
      throw StateError('Sin token');
    }
    final uri = Uri.parse(_buildUrl('asistencia/update'));
    final payload = <String, dynamic>{
      'field': field,
      'value': value,
      'hora_apertura_mesa': horaAperturaMesa,
      'latitud': latitud,
      'longitud': longitud,
      'presente_at': presenteAt,
    }..removeWhere((_, value) => value == null);
    final res = await _client
        .post(
          uri,
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: jsonEncode(payload),
        )
        .timeout(const Duration(seconds: 8));
    if (res.statusCode < 200 || res.statusCode >= 300) {
      String message = 'Error al enviar asistencia';
      try {
        final parsed = jsonDecode(res.body);
        if (parsed is Map && parsed['message'] != null) {
          message = parsed['message'].toString();
        }
      } catch (_) {}
      throw StateError(message);
    }
  }

  Future<int> flushQueue() async {
    final queue = await _localStore.readAsistenciaQueue();
    var ok = 0;
    for (final item in queue) {
      final field = item['field']?.toString() ?? '';
      final value = item['value'] == true;
      final horaAperturaMesa = item['hora_apertura_mesa']?.toString();
      final latitud = item['latitud'] as double?;
      final longitud = item['longitud'] as double?;
      final presenteAt = item['presente_at']?.toString();
      if (field.isEmpty) continue;
      try {
        await sendAsistenciaToggle(
          field: field,
          value: value,
          horaAperturaMesa: horaAperturaMesa?.isEmpty == true
              ? null
              : horaAperturaMesa,
          latitud: latitud,
          longitud: longitud,
          presenteAt: presenteAt?.isEmpty == true ? null : presenteAt,
        );
        await _localStore.dequeueAsistenciaField(field);
        ok++;
      } catch (_) {
        // si falla, mantenemos pendiente
      }
    }
    return ok;
  }
}
