import 'mobile_asistencia_service.dart';
import 'mobile_auth_local_store.dart';
import 'mobile_votacion_service.dart';

class MobileSyncResult {
  const MobileSyncResult({
    required this.asistenciaEnviados,
    required this.votacionEnviados,
    required this.pendingCount,
  });

  final int asistenciaEnviados;
  final int votacionEnviados;
  final int pendingCount;
}

class MobileSyncService {
  MobileSyncService({
    MobileAuthLocalStore? localStore,
    MobileAsistenciaService? asistenciaService,
    MobileVotacionService? votacionService,
  }) : _localStore = localStore ?? MobileAuthLocalStore.instance,
       _asistenciaService = asistenciaService ?? MobileAsistenciaService(),
       _votacionService = votacionService ?? MobileVotacionService();

  final MobileAuthLocalStore _localStore;
  final MobileAsistenciaService _asistenciaService;
  final MobileVotacionService _votacionService;

  Future<MobileSyncResult> syncAll() async {
    final asistenciaOk = await _asistenciaService.flushQueue();

    final pendings = await _localStore.readPendingVotacionDrafts();
    var votacionOk = 0;
    for (final d in pendings) {
      try {
        await _votacionService.sendVotacion(d);
        await _localStore.markVotacionSynced(
          d.mesaId,
          finalizada: d.finalizar,
        );
        votacionOk++;
      } catch (e) {
        await _localStore.markVotacionError(d.mesaId, e.toString());
      }
    }

    final pendingCount = await _localStore.getPendingSyncCount();

    return MobileSyncResult(
      asistenciaEnviados: asistenciaOk,
      votacionEnviados: votacionOk,
      pendingCount: pendingCount,
    );
  }
}
