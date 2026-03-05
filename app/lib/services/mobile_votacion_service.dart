import 'dart:convert';
import 'dart:io';

import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

import '../models/votacion_model.dart';
import 'mobile_auth_local_store.dart';

class MobileVotacionService {
  MobileVotacionService({MobileAuthLocalStore? localStore, http.Client? client})
    : _localStore = localStore ?? MobileAuthLocalStore.instance,
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

  Future<Map<String, dynamic>> loadCatalogo() async {
    final token = await _localStore.readAuthToken();
    if (token == null || token.isEmpty) {
      throw StateError('Sin token');
    }
    final res = await _client
        .get(
          Uri.parse(_buildUrl('votacion/catalogo')),
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
          },
        )
        .timeout(const Duration(seconds: 8));
    if (res.statusCode != 200) {
      throw StateError('No se pudo cargar catalogo');
    }
    final body = jsonDecode(res.body);
    if (body is! Map<String, dynamic>) {
      throw const FormatException('Catalogo invalido');
    }
    return body;
  }

  Future<Map<String, dynamic>> loadMesa(int mesaId) async {
    final token = await _localStore.readAuthToken();
    if (token == null || token.isEmpty) {
      throw StateError('Sin token');
    }
    final res = await _client
        .get(
          Uri.parse(_buildUrl('votacion/mesa/$mesaId')),
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/json',
          },
        )
        .timeout(const Duration(seconds: 8));
    if (res.statusCode != 200) {
      throw StateError('No se pudo cargar mesa');
    }
    final body = jsonDecode(res.body);
    if (body is! Map<String, dynamic>) {
      throw const FormatException('Mesa invalida');
    }
    return body;
  }

  Future<void> sendVotacion(VotacionDraft draft) async {
    final token = await _localStore.readAuthToken();
    if (token == null || token.isEmpty) {
      throw StateError('Sin token');
    }
    final req =
        http.MultipartRequest(
            'POST',
            Uri.parse(_buildUrl('votacion/mesa/${draft.mesaId}/guardar')),
          )
          ..headers['Authorization'] = 'Bearer $token'
          ..headers['Accept'] = 'application/json'
          ..fields['finalizar'] = draft.finalizar ? '1' : '0'
          ..fields['observacion'] = draft.observacion ?? ''
          ..fields['observacion_gobernador'] = draft.observacionGobernador ?? ''
          ..fields['observacion_asambleista_distrito'] = draft.observacionAsd ?? ''
          ..fields['observacion_asambleista_poblacion'] = draft.observacionAsp ?? ''
          ..fields['observacion_concejal'] = draft.observacionConcejal ?? ''
          ..fields['observacion_alcalde'] = draft.observacionAlcalde ?? ''
          ..fields['blancos_gobernador'] = '${draft.blancosGobernador}'
          ..fields['nulos_gobernador'] = '${draft.nulosGobernador}'
          ..fields['papeletas_no_utilizadas_gobernador'] =
              '${draft.papeletasNoUtilizadasGobernador}'
          ..fields['blancos_asambleista_distrito'] = '${draft.blancosAsd}'
          ..fields['nulos_asambleista_distrito'] = '${draft.nulosAsd}'
          ..fields['papeletas_no_utilizadas_asambleista_distrito'] =
              '${draft.papeletasNoUtilizadasAsd}'
          ..fields['blancos_asambleista_poblacion'] = '${draft.blancosAsp}'
          ..fields['nulos_asambleista_poblacion'] = '${draft.nulosAsp}'
          ..fields['papeletas_no_utilizadas_asambleista_poblacion'] =
              '${draft.papeletasNoUtilizadasAsp}'
          ..fields['blancos_concejal'] = '${draft.blancosConcejal}'
          ..fields['nulos_concejal'] = '${draft.nulosConcejal}'
          ..fields['papeletas_no_utilizadas_concejal'] =
              '${draft.papeletasNoUtilizadasConcejal}'
          ..fields['blancos_alcalde'] = '${draft.blancosAlcalde}'
          ..fields['nulos_alcalde'] = '${draft.nulosAlcalde}'
          ..fields['papeletas_no_utilizadas_alcalde'] =
              '${draft.papeletasNoUtilizadasAlcalde}'
          ..fields['votos'] = jsonEncode(
            draft.votos
                .map(
                  (v) => {
                    'partido_id': v.partidoId,
                    'votos_gobernador': v.votosGobernador,
                    'votos_asambleista_distrito': v.votosAsd,
                    'votos_asambleista_poblacion': v.votosAsp,
                    'votos_concejal': v.votosConcejal,
                    'votos_alcalde': v.votosAlcalde,
                  },
                )
                .toList(),
          );

    for (final slot in votacionFotoSlots) {
      final path = draft.fotos[slot];
      if (path != null && path.isNotEmpty && await File(path).exists()) {
        req.files.add(
          await http.MultipartFile.fromPath(
            slot,
            path,
            contentType: _contentTypeForPath(path),
          ),
        );
      }
    }

    final streamed = await req.send().timeout(const Duration(seconds: 60));
    final body = await streamed.stream.bytesToString();
    if (streamed.statusCode < 200 || streamed.statusCode >= 300) {
      String? backendMessage;
      try {
        final parsed = jsonDecode(body);
        if (parsed is Map && parsed['message'] != null) {
          backendMessage = parsed['message'].toString();
        }
      } catch (_) {}
      if (backendMessage != null && backendMessage.isNotEmpty) {
        throw StateError(backendMessage);
      }
      throw StateError('Error al enviar votacion (${streamed.statusCode})');
    }
  }

  MediaType _contentTypeForPath(String path) {
    final p = path.toLowerCase();
    if (p.endsWith('.webp')) return MediaType('image', 'webp');
    if (p.endsWith('.png')) return MediaType('image', 'png');
    return MediaType('image', 'jpeg');
  }

  Future<String> compressImageToWebp({
    required String sourcePath,
    required String targetPath,
  }) async {
    final compressed = await FlutterImageCompress.compressAndGetFile(
      sourcePath,
      targetPath,
      quality: 90,
      format: CompressFormat.webp,
      keepExif: true,
    );
    if (compressed == null) {
      await File(sourcePath).copy(targetPath);
      return targetPath;
    }
    return compressed.path;
  }

  Future<String?> downloadAndCompressImageToWebp({
    required String imageUrl,
    required String targetPath,
  }) async {
    final uri = Uri.tryParse(imageUrl);
    if (uri == null) return null;
    final res = await _client
        .get(uri, headers: {'Accept': 'image/*'})
        .timeout(const Duration(seconds: 10));
    if (res.statusCode < 200 || res.statusCode >= 300) {
      return null;
    }
    final tmpSource = '$targetPath.src';
    await File(tmpSource).writeAsBytes(res.bodyBytes, flush: true);
    try {
      return await compressImageToWebp(
        sourcePath: tmpSource,
        targetPath: targetPath,
      );
    } finally {
      final tmp = File(tmpSource);
      if (await tmp.exists()) {
        await tmp.delete();
      }
    }
  }

  Future<String> compressImageToJpeg({
    required String sourcePath,
    required String targetPath,
  }) => compressImageToWebp(sourcePath: sourcePath, targetPath: targetPath);

  Future<String?> downloadAndCompressImageToJpeg({
    required String imageUrl,
    required String targetPath,
  }) => downloadAndCompressImageToWebp(imageUrl: imageUrl, targetPath: targetPath);
}
