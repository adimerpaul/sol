import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_map_tile_caching/flutter_map_tile_caching.dart';
import 'package:latlong2/latlong.dart';

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
                          child: _RecintoMarker(recinto: recinto),
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
}

class _RecintoMarker extends StatelessWidget {
  const _RecintoMarker({required this.recinto});

  final RecintoMesaPoint recinto;

  @override
  Widget build(BuildContext context) {
    return Card(
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
    );
  }
}
