import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

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
        final celular = profile.user.celular;
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
              ],
              extra: _WhatsAppLine(
                celular: celular,
                onTap: () => _openWhatsApp(celular),
              ),
            ),
            const SizedBox(height: 12),
            _InfoCard(
              title: 'Supervisores',
              lines: const [],
              extra: _ContactList(
                emptyText: 'Sin supervisores asignados',
                items: profile.supervisores
                    .map((s) => _ContactItem(name: s.name, celular: s.celular))
                    .toList(),
                onWhatsAppTap: _openWhatsApp,
              ),
            ),
            const SizedBox(height: 12),
            _InfoCard(
              title: 'Jefes de recinto',
              lines: const [],
              extra: _ContactList(
                emptyText: 'Sin jefes asignados',
                items: profile.jefes
                    .map((j) => _ContactItem(name: j.name, celular: j.celular))
                    .toList(),
                onWhatsAppTap: _openWhatsApp,
              ),
            ),
            const SizedBox(height: 12),
            _InfoCard(title: 'App', lines: ['Version: ${data.versionLabel}']),
          ],
        );
      },
    );
  }

  Future<void> _openWhatsApp(String? rawPhone) async {
    final phone = _normalizePhone(rawPhone);
    if (phone == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No hay celular valido para WhatsApp')),
      );
      return;
    }

    final uri = Uri.parse('https://wa.me/$phone');
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo abrir WhatsApp')),
      );
    }
  }

  String? _normalizePhone(String? input) {
    if (input == null) return null;
    final digits = input.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.isEmpty) return null;
    return digits;
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.title, required this.lines, this.extra});

  final String title;
  final List<String> lines;
  final Widget? extra;

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
            if (extra != null) ...[const SizedBox(height: 6), extra!],
          ],
        ),
      ),
    );
  }
}

class _WhatsAppLine extends StatelessWidget {
  const _WhatsAppLine({required this.celular, required this.onTap});

  final String? celular;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final hasPhone = (celular ?? '').trim().isNotEmpty;
    return Row(
      children: [
        const Text('Celular: '),
        Expanded(child: Text(hasPhone ? celular!.trim() : '-')),
        IconButton(
          tooltip: 'Abrir WhatsApp',
          onPressed: hasPhone ? onTap : null,
          icon: const Icon(Icons.chat, color: Color(0xFF25D366)),
        ),
      ],
    );
  }
}

class _ContactItem {
  const _ContactItem({required this.name, required this.celular});

  final String name;
  final String? celular;
}

class _ContactList extends StatelessWidget {
  const _ContactList({
    required this.emptyText,
    required this.items,
    required this.onWhatsAppTap,
  });

  final String emptyText;
  final List<_ContactItem> items;
  final Future<void> Function(String? celular) onWhatsAppTap;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) return Text(emptyText);

    return Column(
      children: items
          .map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                children: [
                  Expanded(
                    child: Text('${item.name} | Cel: ${item.celular ?? '-'}'),
                  ),
                  IconButton(
                    tooltip: 'Abrir WhatsApp',
                    onPressed: ((item.celular ?? '').trim().isEmpty)
                        ? null
                        : () => onWhatsAppTap(item.celular),
                    icon: const Icon(Icons.chat, color: Color(0xFF25D366)),
                  ),
                ],
              ),
            ),
          )
          .toList(),
    );
  }
}

class _PerfilUiData {
  _PerfilUiData({required this.profile, required this.versionLabel});

  final MobileProfileData? profile;
  final String versionLabel;
}
