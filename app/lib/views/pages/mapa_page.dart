import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_map_tile_caching/flutter_map_tile_caching.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../services/mobile_auth_local_store.dart';

class MapaPage extends StatefulWidget {
  const MapaPage({super.key, this.zoom = 13.8});

  final double zoom;

  @override
  State<MapaPage> createState() => _MapaPageState();
}

class _MapaPageState extends State<MapaPage> {
  bool _mapaNormal = true;
  late Future<List<RecintoMesaPoint>> _futureRecintos;

  @override
  void initState() {
    super.initState();
    _futureRecintos = MobileAuthLocalStore.instance.getRecintosDesdeMesas();
  }

  LatLng _resolveCenter(List<RecintoMesaPoint> recintos) {
    if (recintos.isNotEmpty) {
      return LatLng(recintos.first.latitud, recintos.first.longitud);
    }
    return const LatLng(-17.968176, -67.112584);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<RecintoMesaPoint>>(
      future: _futureRecintos,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        final recintos = snapshot.data ?? const <RecintoMesaPoint>[];
        final center = _resolveCenter(recintos);

        return Stack(
          children: [
            FlutterMap(
              options: MapOptions(
                initialCenter: center,
                initialZoom: widget.zoom,
              ),
              children: [
                TileLayer(
                  key: ValueKey(_mapaNormal),
                  urlTemplate: _mapaNormal
                      ? 'https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}'
                      : 'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
                  userAgentPackageName: 'com.example.app',
                  tileProvider: FMTCTileProvider(
                    stores: const {
                      'mapStore': BrowseStoreStrategy.readUpdateCreate,
                    },
                    loadingStrategy: BrowseLoadingStrategy.onlineFirst,
                  ),
                ),
                MarkerLayer(
                  markers: recintos
                      .map(
                        (recinto) => Marker(
                          point: LatLng(recinto.latitud, recinto.longitud),
                          width: 140,
                          height: 52,
                          child: _RecintoMarker(
                            recinto: recinto,
                            onTap: () => _showRecintoDialog(recinto),
                          ),
                        ),
                      )
                      .toList(),
                ),
              ],
            ),
            Positioned(
              right: 16,
              top: 16,
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  child: Text('Recintos: ${recintos.length}'),
                ),
              ),
            ),
            Positioned(
              right: 16,
              bottom: 16,
              child: FloatingActionButton(
                heroTag: 'toggle_map_mode',
                mini: true,
                onPressed: () {
                  setState(() {
                    _mapaNormal = !_mapaNormal;
                  });
                },
                child: Icon(_mapaNormal ? Icons.satellite_alt : Icons.map),
              ),
            ),
          ],
        );
      },
    );
  }

  Future<void> _showRecintoDialog(RecintoMesaPoint recinto) async {
    if (!mounted) return;
    await showDialog<void>(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          title: Text(recinto.recintoNombre),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Total mesas: ${recinto.totalMesas}'),
              Text('Pendientes: ${recinto.pendientes}'),
              Text('Locales: ${recinto.locales}'),
              Text('Realizados: ${recinto.realizados}'),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('Cerrar'),
            ),
            FilledButton.icon(
              onPressed: () async {
                Navigator.of(ctx).pop();
                await _openGoogleMaps(recinto);
              },
              icon: const Icon(Icons.directions),
              label: const Text('Ir con Google Maps'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _openGoogleMaps(RecintoMesaPoint recinto) async {
    final lat = recinto.latitud;
    final lng = recinto.longitud;
    final uri = Uri.parse(
      'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng&travelmode=driving',
    );
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo abrir Google Maps')),
      );
    }
  }
}

class _RecintoMarker extends StatelessWidget {
  const _RecintoMarker({required this.recinto, required this.onTap});

  final RecintoMesaPoint recinto;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Card(
          elevation: 3,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
            child: Row(
              children: [
                const Icon(Icons.place, color: Colors.red, size: 20),
                const SizedBox(width: 4),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        recinto.recintoNombre,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        'Mesas: ${recinto.totalMesas}',
                        style: const TextStyle(fontSize: 10),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
