import 'dart:io';
import 'package:flutter/material.dart';
import 'package:cunning_document_scanner/cunning_document_scanner.dart';
import 'package:image/image.dart' as img;
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;

enum ImageFilterType {
  original,
  documentoClaro, // Brillo/Contraste/Nitidez
  escalaGrises,
  blancoYNegro // Threshold
}

class ImageEnhanceScreen extends StatefulWidget {
  final String imagePath;

  const ImageEnhanceScreen({
    super.key,
    required this.imagePath,
  });

  @override
  State<ImageEnhanceScreen> createState() => _ImageEnhanceScreenState();
}

class _ImageEnhanceScreenState extends State<ImageEnhanceScreen>
    with SingleTickerProviderStateMixin {
  bool _isProcessing = false;
  ImageFilterType _selectedFilter = ImageFilterType.original;
  late img.Image _originalImage;
  File? _displayFile;
  late AnimationController _scanAnimationController;
  late Animation<double> _scanAnimation;

  @override
  void initState() {
    super.initState();
    _displayFile = File(widget.imagePath);

    _scanAnimationController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    );

    _scanAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _scanAnimationController,
        curve: Curves.easeInOutSine,
      ),
    )..addStatusListener((status) {
        if (status == AnimationStatus.completed) {
          _scanAnimationController.reverse();
        } else if (status == AnimationStatus.dismissed) {
          _scanAnimationController.forward();
        }
      });

    _loadOriginalImage();
  }

  @override
  void dispose() {
    _scanAnimationController.dispose();
    super.dispose();
  }

  static Future<img.Image?> _decodeImageWorker(Uint8List bytes) async {
    return img.decodeImage(bytes);
  }

  Future<void> _loadOriginalImage() async {
    setState(() => _isProcessing = true);
    _scanAnimationController.forward();
    
    try {
      // Leemos archivo rapido
      final bytes = await File(widget.imagePath).readAsBytes();
      
      // Enviamos la parte pesada al Worker (que no traba la animación de la pantalla)
      // Agregamos un mínimo de tiempo (ej. 800ms) para que el ojo humano pueda apreciar 
      // la animación del láser que escanea.
      final results = await Future.wait([
        compute(_decodeImageWorker, bytes),
        Future.delayed(const Duration(milliseconds: 1200))
      ]);

      final decodedImage = results[0] as img.Image?;
      if (decodedImage != null) {
        _originalImage = decodedImage;
      }
    } catch (e) {
      debugPrint("Error decodificando imagen original: $e");
    } finally {
      if (mounted) {
        setState(() => _isProcessing = false);
        _scanAnimationController.stop();
        _scanAnimationController.reset();
      }
    }
  }

  Future<void> _applyFilter(ImageFilterType filterType) async {
    if (_selectedFilter == filterType) return;

    setState(() {
      _selectedFilter = filterType;
      _isProcessing = true;
    });

    _scanAnimationController.forward();

    try {
      // Usar compute REAL para no bloquear la UI en ningún momento.
      // Se pasan los bytes originales al Isolate para evitar sobrecarga de paso de objetos.
      final originalBytes = await File(widget.imagePath).readAsBytes();
      
      final newFilePaths = await compute(
        _processImageWorker, 
        {'bytes': originalBytes, 'type': filterType.index}
      );
      
      setState(() {
        _displayFile = File(newFilePaths);
      });
    } catch (e) {
      debugPrint("Error aplicando filtro: $e");
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error al procesar la imagen')),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isProcessing = false;
        });
        _scanAnimationController.stop();
        _scanAnimationController.reset();
      }
    }
  }

  static Future<String> _processImageWorker(Map<String, dynamic> args) async {
    final Uint8List originalBytes = args['bytes'];
    final int typeIndex = args['type'];
    final filterType = ImageFilterType.values[typeIndex];

    img.Image? decoded = img.decodeImage(originalBytes);
    if (decoded == null) throw Exception("No se pudo decodificar la imagen");

    img.Image processed = decoded;

    switch (filterType) {
      case ImageFilterType.original:
        // Original exacto sin alteraciones.
        processed = decoded;
        break;
      case ImageFilterType.documentoClaro:
        // "IA Magic": Aumentamos el brillo y contraste directamente sin normalizar
        // para evitar artefactos extraños. Subimos la saturación para revivir colores.
        img.adjustColor(decoded, brightness: 1.2, contrast: 1.25, saturation: 1.2);
        processed = decoded;
        break;
      case ImageFilterType.escalaGrises:
        // Grises limpio con un ligero aumento de contraste
        processed = img.grayscale(decoded);
        img.adjustColor(processed, brightness: 1.1, contrast: 1.25);
        break;
      case ImageFilterType.blancoYNegro:
        // B&W: Para evitar el "relleno blanco" en las letras en negrita que causaba
        // el contraste extremo, volvemos a un "Threshold" (umbral) muy balanceado.
        // Esto pasa todos los grises oscuros a negro, y los claros a blanco puro.
        processed = img.grayscale(decoded);
        img.luminanceThreshold(processed, threshold: 0.55);
        break;
    }

    final tempDir = Directory.systemTemp;
    final String tempFilePath = '${tempDir.path}/preview_filter_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final tempFile = File(tempFilePath);
    await tempFile.writeAsBytes(img.encodeJpg(processed, quality: 75));
    
    return tempFilePath;
  }

  Future<void> _retakePhoto() async {
    try {
      List<String>? pictures = await CunningDocumentScanner.getPictures(
        isGalleryImportAllowed: true,
        noOfPages: 1,
      );

      if (pictures != null && pictures.isNotEmpty && mounted) {
        // En lugar de pushear, reemplazamos por completo la imagen que tenemos
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => ImageEnhanceScreen(imagePath: pictures.first),
          ),
        );
      }
    } catch (e) {
      debugPrint('Error volviendo a tomar foto: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1E1E1E), // Gris oscuro muy sutil
      appBar: AppBar(
        title: const Text('Recorte y Filtros'),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.white,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context), // Cierra sin retornar imagen si no quiere guardarla
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.camera_alt_outlined),
            tooltip: 'Tomar otra foto',
            onPressed: _isProcessing ? null : _retakePhoto,
          ),
          Padding(
            padding: const EdgeInsets.only(right: 8.0, left: 4),
            child: TextButton(
              onPressed: _isProcessing
                  ? null
                  : () => Navigator.pop(context, _displayFile?.path ?? widget.imagePath),
              style: TextButton.styleFrom(
                backgroundColor: _isProcessing ? Colors.grey : Colors.green[600],
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8)
              ),
              child: const Text('LISTO',
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          )
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Área de Imagen Central (margen y borde redondeado)
            Expanded(
              child: Container(
                margin: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.black,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.5),
                      blurRadius: 10,
                      spreadRadius: 2,
                    )
                  ]
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Stack(
                    alignment: Alignment.center,
                    fit: StackFit.expand,
                    children: [
                      if (_displayFile != null)
                        InteractiveViewer(
                          child: Image.file(_displayFile!, fit: BoxFit.contain),
                        ),

                      // Animación Escáner Premium
                      if (_isProcessing)
                        Positioned.fill(
                          child: AnimatedBuilder(
                            animation: _scanAnimation,
                            builder: (context, child) {
                              final scanPosition = _scanAnimation.value * MediaQuery.of(context).size.height * 0.7;
                              return Stack(
                                children: [
                                  // Fondo azulino/verdoso de "análisis"
                                  Container(color: const Color(0xFF001F3F).withOpacity(0.2)),
                                  
                                  // Haz de luz súper fino
                                  Positioned(
                                    top: scanPosition,
                                    left: 0,
                                    right: 0,
                                    child: Container(
                                      height: 120, // Zona de destello
                                      decoration: BoxDecoration(
                                        gradient: LinearGradient(
                                          begin: Alignment.topCenter,
                                          end: Alignment.bottomCenter,
                                          colors: [
                                            Colors.transparent,
                                            Colors.cyanAccent.withOpacity(0.1),
                                            Colors.cyanAccent.withOpacity(0.6),
                                          ],
                                          stops: const [0.0, 0.7, 1.0],
                                        )
                                      ),
                                      child: Align(
                                        alignment: Alignment.bottomCenter,
                                        child: Container(
                                          height: 2, // Fino como un láser
                                          decoration: BoxDecoration(
                                            color: Colors.cyanAccent,
                                            boxShadow: [
                                              BoxShadow(
                                                color: Colors.cyanAccent.withOpacity(0.9),
                                                blurRadius: 6,
                                                spreadRadius: 1,
                                              )
                                            ]
                                          ),
                                        ),
                                      ),
                                    ),
                                  )
                                ],
                              );
                            },
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            
            // Selector de filtros 
            Container(
              padding: const EdgeInsets.symmetric(vertical: 20),
              decoration: const BoxDecoration(
                color: Colors.black,
                border: Border(top: BorderSide(color: Color(0xFF333333), width: 1))
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _FilterOption(
                    title: 'Original',
                    icon: Icons.image_outlined,
                    isSelected: _selectedFilter == ImageFilterType.original,
                    onTap: () => _applyFilter(ImageFilterType.original),
                  ),
                  _FilterOption(
                    title: 'IA Magic',
                    icon: Icons.auto_awesome,
                    isSelected: _selectedFilter == ImageFilterType.documentoClaro,
                    onTap: () => _applyFilter(ImageFilterType.documentoClaro),
                  ),
                  _FilterOption(
                    title: 'B&W',
                    icon: Icons.document_scanner_outlined,
                    isSelected: _selectedFilter == ImageFilterType.blancoYNegro,
                    onTap: () => _applyFilter(ImageFilterType.blancoYNegro),
                  ),
                  _FilterOption(
                    title: 'Grises',
                    icon: Icons.filter_b_and_w,
                    isSelected: _selectedFilter == ImageFilterType.escalaGrises,
                    onTap: () => _applyFilter(ImageFilterType.escalaGrises),
                  ),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }
}

class _FilterOption extends StatelessWidget {
  final String title;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  const _FilterOption({
    required this.title,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF003333) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              color: isSelected ? Colors.cyanAccent : Colors.grey[400],
              size: 28,
            ),
            const SizedBox(height: 6),
            Text(
              title,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: isSelected ? Colors.cyanAccent : Colors.grey[400],
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              ),
            )
          ],
        ),
      ),
    );
  }
}
