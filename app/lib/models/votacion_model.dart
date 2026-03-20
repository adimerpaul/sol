class VotacionDraft {
  VotacionDraft({
    required this.mesaId,
    required this.finalizar,
    required this.observacion,
    required this.observacionGobernador,
    required this.observacionAsd,
    required this.observacionAsp,
    required this.observacionConcejal,
    required this.observacionAlcalde,
    required this.blancosGobernador,
    required this.nulosGobernador,
    required this.papeletasNoUtilizadasGobernador,
    required this.blancosAsd,
    required this.nulosAsd,
    required this.papeletasNoUtilizadasAsd,
    required this.blancosAsp,
    required this.nulosAsp,
    required this.papeletasNoUtilizadasAsp,
    required this.blancosConcejal,
    required this.nulosConcejal,
    required this.papeletasNoUtilizadasConcejal,
    required this.blancosAlcalde,
    required this.nulosAlcalde,
    required this.papeletasNoUtilizadasAlcalde,
    required this.lockAlcalde,
    required this.lockConcejal,
    required this.lockGobernador,
    required this.lockAsd,
    required this.lockAsp,
    required this.votos,
    required this.fotos,
    required this.enviadoFinal,
    required this.syncStatus,
    required this.updatedAt,
  });

  final int mesaId;
  final bool finalizar;
  final String? observacion;
  final String? observacionGobernador;
  final String? observacionAsd;
  final String? observacionAsp;
  final String? observacionConcejal;
  final String? observacionAlcalde;

  final int blancosGobernador;
  final int nulosGobernador;
  final int papeletasNoUtilizadasGobernador;
  final int blancosAsd;
  final int nulosAsd;
  final int papeletasNoUtilizadasAsd;
  final int blancosAsp;
  final int nulosAsp;
  final int papeletasNoUtilizadasAsp;
  final int blancosConcejal;
  final int nulosConcejal;
  final int papeletasNoUtilizadasConcejal;
  final int blancosAlcalde;
  final int nulosAlcalde;
  final int papeletasNoUtilizadasAlcalde;
  final bool lockAlcalde;
  final bool lockConcejal;
  final bool lockGobernador;
  final bool lockAsd;
  final bool lockAsp;

  final List<VotoPartidoItem> votos;
  final Map<String, String?> fotos;
  final bool enviadoFinal;
  final String syncStatus;
  final String updatedAt;
}

const List<String> votacionFotoSlots = <String>[
  'foto1',
  'foto2',
  'foto3',
  'foto4',
  'foto5',
  'foto6',
  'foto7',
  'foto8',
  'foto9',
  'foto10',
];

class VotoPartidoItem {
  VotoPartidoItem({
    required this.partidoId,
    required this.sigla,
    required this.nombre,
    required this.iconoUrl,
    required this.votosGobernador,
    required this.votosAsd,
    required this.votosAsp,
    required this.votosConcejal,
    required this.votosAlcalde,
  });

  final int partidoId;
  final String sigla;
  final String nombre;
  final String? iconoUrl;
  final int votosGobernador;
  final int votosAsd;
  final int votosAsp;
  final int votosConcejal;
  final int votosAlcalde;
}
