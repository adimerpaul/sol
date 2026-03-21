import 'dart:io';

import 'package:flutter/services.dart';
import 'package:path_provider/path_provider.dart';

class ManualPdfService {
  ManualPdfService._();

  static const MethodChannel _channel = MethodChannel('com.example.app/manuals');

  static Future<void> saveAndOpenManual({
    required String assetPath,
    required String fileName,
  }) async {
    final tempPath = await _copyAssetToTemp(
      assetPath: assetPath,
      fileName: fileName,
    );

    await _channel.invokeMethod<void>('saveAndOpenPdf', {
      'sourcePath': tempPath,
      'fileName': fileName,
      'subdirectory': 'Jacha',
    });
  }

  static Future<String> _copyAssetToTemp({
    required String assetPath,
    required String fileName,
  }) async {
    final byteData = await rootBundle.load(assetPath);
    final bytes = byteData.buffer.asUint8List(
      byteData.offsetInBytes,
      byteData.lengthInBytes,
    );

    final tempDir = await getTemporaryDirectory();
    final tempFile = File('${tempDir.path}/$fileName');
    await tempFile.writeAsBytes(bytes, flush: true);
    return tempFile.path;
  }
}
