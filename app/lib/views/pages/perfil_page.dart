import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../services/mobile_auth_local_store.dart';

class PerfilPage extends StatefulWidget {
  const PerfilPage({super.key});

  @override
  State<PerfilPage> createState() => _PerfilPageState();
}

class _PerfilPageState extends State<PerfilPage> {
  late Future<_PerfilUiData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_PerfilUiData> _load() async {
    final profile = await MobileAuthLocalStore.instance.readProfileData();
    final package = await PackageInfo.fromPlatform();
    return _PerfilUiData(
      profile: profile,
      versionLabel: '${package.version}+${package.buildNumber}',
    );
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<_PerfilUiData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        final data = snapshot.data;
        if (data == null || data.profile == null) {
          return const Center(
            child: Text('No hay datos de perfil disponibles en SQLite.'),
          );
        }
        final profile = data.profile!;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text(
              'Perfil',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            _InfoCard(
              title: 'Usuario',
              lines: [
                'Nombre: ${profile.user.name}',
                'CI: ${profile.user.ci}',
                'Rol: ${profile.user.role ?? '-'}',
                'Celular: ${profile.user.celular ?? '-'}',
              ],
            ),
            const SizedBox(height: 12),
            _InfoCard(
              title: 'Supervisores',
              lines: profile.supervisores.isEmpty
                  ? const ['Sin supervisores asignados']
                  : profile.supervisores
                        .map((s) => '${s.name} | Cel: ${s.celular ?? '-'}')
                        .toList(),
            ),
            const SizedBox(height: 12),
            _InfoCard(
              title: 'Jefes de recinto',
              lines: profile.jefes.isEmpty
                  ? const ['Sin jefes asignados']
                  : profile.jefes
                        .map((j) => '${j.name} | Cel: ${j.celular ?? '-'}')
                        .toList(),
            ),
            const SizedBox(height: 12),
            _InfoCard(title: 'App', lines: ['Version: ${data.versionLabel}']),
          ],
        );
      },
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.title, required this.lines});

  final String title;
  final List<String> lines;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            ...lines.map(
              (line) => Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Text(line),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PerfilUiData {
  _PerfilUiData({required this.profile, required this.versionLabel});

  final MobileProfileData? profile;
  final String versionLabel;
}
