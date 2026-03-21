import 'package:font_awesome_flutter/font_awesome_flutter.dart';
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
  static final Uri _apoyoUri = Uri.parse(
    'https://chat.whatsapp.com/LvVhubY9BMY93m0pR5HaIE?mode=gi_t',
  );
  static final Uri _sistemasUri = Uri.parse(
    'https://chat.whatsapp.com/Lf7zEfkXIDq1niBSH0lEzR',
  );

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
          padding: const EdgeInsets.all(12),
          children: [
            const Text(
              'Perfil',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            _InfoCard(
              title: 'Usuario',
              children: [
                _InfoLine(
                  label: 'Nombre',
                  value: profile.user.name,
                  boldValue: true,
                ),
                _InfoLine(label: 'CI', value: profile.user.ci),
                _InfoLine(
                  label: 'Rol',
                  value: profile.user.role ?? '-',
                  boldValue: true,
                ),
                _WhatsAppLine(
                  label: 'Celular',
                  celular: celular,
                  boldValue: true,
                  onTap: () => _openWhatsApp(celular),
                ),
                _LinkLine(
                  label: 'Apoyo',
                  subtitle: 'Grupo de WhatsApp',
                  onTap: () => _openLink(_apoyoUri),
                ),
                _LinkLine(
                  label: 'Consulta sistemas',
                  subtitle: 'Grupo de WhatsApp',
                  onTap: () => _openLink(_sistemasUri),
                ),
              ],
            ),
            const SizedBox(height: 8),
            _InfoCard(
              title: 'Supervisores',
              children: [
                _ContactList(
                  emptyText: 'Sin supervisores asignados',
                  items: profile.supervisores
                      .map((s) => _ContactItem(name: s.name, celular: s.celular))
                      .toList(),
                  onWhatsAppTap: _openWhatsApp,
                ),
              ],
            ),
            const SizedBox(height: 8),
            _InfoCard(
              title: 'Jefes de recinto',
              children: [
                _ContactList(
                  emptyText: 'Sin jefes asignados',
                  items: profile.jefes
                      .map((j) => _ContactItem(name: j.name, celular: j.celular))
                      .toList(),
                  onWhatsAppTap: _openWhatsApp,
                ),
              ],
            ),
            const SizedBox(height: 8),
            _InfoCard(
              title: 'App',
              children: [
                _InfoLine(label: 'Version', value: data.versionLabel),
              ],
            ),
          ],
        );
      },
    );
  }

  Future<void> _openLink(Uri uri) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo abrir el enlace')),
      );
    }
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
  const _InfoCard({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 6),
            ...children,
          ],
        ),
      ),
    );
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({
    required this.label,
    required this.value,
    this.boldValue = false,
  });

  final String label;
  final String value;
  final bool boldValue;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: RichText(
        text: TextSpan(
          style: DefaultTextStyle.of(context).style.copyWith(fontSize: 13),
          children: [
            TextSpan(text: '$label: '),
            TextSpan(
              text: value,
              style: TextStyle(
                fontWeight: boldValue ? FontWeight.w700 : FontWeight.w400,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _WhatsAppLine extends StatelessWidget {
  const _WhatsAppLine({
    required this.label,
    required this.celular,
    required this.onTap,
    this.boldValue = false,
  });

  final String label;
  final String? celular;
  final VoidCallback onTap;
  final bool boldValue;

  @override
  Widget build(BuildContext context) {
    final hasPhone = (celular ?? '').trim().isNotEmpty;
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          Expanded(
            child: RichText(
              text: TextSpan(
                style: DefaultTextStyle.of(context).style.copyWith(fontSize: 13),
                children: [
                  TextSpan(text: '$label: '),
                  TextSpan(
                    text: hasPhone ? celular!.trim() : '-',
                    style: TextStyle(
                      fontWeight: boldValue ? FontWeight.w700 : FontWeight.w400,
                    ),
                  ),
                ],
              ),
            ),
          ),
          IconButton(
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
            tooltip: 'Abrir WhatsApp',
            onPressed: hasPhone ? onTap : null,
            icon: const FaIcon(
              FontAwesomeIcons.whatsapp,
              color: Color(0xFF25D366),
              size: 20,
            ),
          ),
        ],
      ),
    );
  }
}

class _LinkLine extends StatelessWidget {
  const _LinkLine({
    required this.label,
    required this.subtitle,
    required this.onTap,
  });

  final String label;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(fontSize: 13)),
                Text(
                  subtitle,
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade700),
                ),
              ],
            ),
          ),
          IconButton(
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
            tooltip: 'Abrir WhatsApp',
            onPressed: onTap,
            icon: const FaIcon(
              FontAwesomeIcons.whatsapp,
              color: Color(0xFF25D366),
              size: 20,
            ),
          ),
        ],
      ),
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
    if (items.isEmpty) {
      return Text(emptyText, style: const TextStyle(fontSize: 13));
    }

    return Column(
      children: items
          .map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 2),
              child: Row(
                children: [
                  Expanded(
                    child: RichText(
                      text: TextSpan(
                        style: DefaultTextStyle.of(context).style.copyWith(fontSize: 13),
                        children: [
                          TextSpan(
                            text: item.name,
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          TextSpan(text: ' | Cel: ${item.celular ?? '-'}'),
                        ],
                      ),
                    ),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                    tooltip: 'Abrir WhatsApp',
                    onPressed: ((item.celular ?? '').trim().isEmpty)
                        ? null
                        : () => onWhatsAppTap(item.celular),
                    icon: const FaIcon(
                      FontAwesomeIcons.whatsapp,
                      color: Color(0xFF25D366),
                      size: 20,
                    ),
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
