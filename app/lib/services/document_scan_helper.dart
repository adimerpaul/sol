import 'package:flutter/material.dart';
import 'package:cunning_document_scanner/cunning_document_scanner.dart';

class DocumentScanHelper {
  /// Abre el escáner de documentos nativo (cunning_document_scanner)
  /// y devuelve la ruta temporal de la imagen capturada.
  static Future<String?> scanDocument(BuildContext context) async {
    try {
      // Usar cunning_document_scanner y forzar 1 página solamente
      List<String>? pictures = await CunningDocumentScanner.getPictures(
        isGalleryImportAllowed: true,
        noOfPages: 1,
      );

      if (pictures != null && pictures.isNotEmpty) {
        return pictures.first;
      }
    } catch (e) {
      debugPrint('Error al escanear documento: $e');
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al abrir el escáner: $e')),
        );
      }
    }
    return null;
  }
}
