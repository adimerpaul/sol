import 'package:flutter/material.dart';

import '../services/mobile_auth_local_store.dart';
import 'pages/alcalde_concejal_page.dart';
import 'pages/gobernador_asambleista_page.dart';
import 'pages/mapa_page.dart';
import 'pages/perfil_page.dart';

enum _MenuSection { perfil, alcaldeConcejal, gobernadorAsambleista, mapa }

class MenuView extends StatefulWidget {
  const MenuView({super.key});

  @override
  State<MenuView> createState() => _MenuViewState();
}

class _MenuViewState extends State<MenuView> {
  _MenuSection _section = _MenuSection.mapa;

  Future<void> _goToLogin() async {
    final hasLocalPendings = await MobileAuthLocalStore.instance
        .hasMesasLocalPendientesSync();

    if (!mounted) return;
    if (hasLocalPendings) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'No se puede salir. Hay mesas en estado LOCAL pendientes de sincronizacion.',
          ),
          duration: Duration(seconds: 4),
        ),
      );
      return;
    }

    await MobileAuthLocalStore.instance.clearSession();
    if (!mounted) return;
    Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
  }

  Future<void> _onMenuAction(_MenuSection? value) async {
    if (value == null) return;
    setState(() {
      _section = value;
    });
  }

  Widget _currentPage() {
    switch (_section) {
      case _MenuSection.perfil:
        return const PerfilPage();
      case _MenuSection.alcaldeConcejal:
        return const AlcaldeConcejalPage();
      case _MenuSection.gobernadorAsambleista:
        return const GobernadorAsambleistaPage();
      case _MenuSection.mapa:
        return const MapaPage();
    }
  }

  String _title() {
    switch (_section) {
      case _MenuSection.perfil:
        return 'Perfil';
      case _MenuSection.alcaldeConcejal:
        return 'Alcalde y Concejal';
      case _MenuSection.gobernadorAsambleista:
        return 'Gobernador y Asambleista';
      case _MenuSection.mapa:
        return 'Mapa';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_title()),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) async {
              if (value == 'perfil') {
                await _onMenuAction(_MenuSection.perfil);
              } else if (value == 'alcalde') {
                await _onMenuAction(_MenuSection.alcaldeConcejal);
              } else if (value == 'gobernador') {
                await _onMenuAction(_MenuSection.gobernadorAsambleista);
              } else if (value == 'salir') {
                await _goToLogin();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'perfil', child: Text('Perfil')),
              PopupMenuItem(
                value: 'alcalde',
                child: Text('Alcalde y Concejal'),
              ),
              PopupMenuItem(
                value: 'gobernador',
                child: Text('Gobernador y Asambleista'),
              ),
              PopupMenuItem(value: 'salir', child: Text('Salir')),
            ],
          ),
        ],
      ),
      body: _currentPage(),
      floatingActionButton: FloatingActionButton(
        heroTag: 'go_map_fab',
        onPressed: () {
          setState(() {
            _section = _MenuSection.mapa;
          });
        },
        child: const Icon(Icons.map_outlined),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      bottomNavigationBar: BottomAppBar(
        shape: const CircularNotchedRectangle(),
        notchMargin: 8,
        child: SizedBox(
          height: 58,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _NavButton(
                icon: Icons.person_outline,
                label: 'Perfil',
                selected: _section == _MenuSection.perfil,
                onTap: () => _onMenuAction(_MenuSection.perfil),
              ),
              _NavButton(
                icon: Icons.how_to_vote_outlined,
                label: 'Alcalde',
                selected: _section == _MenuSection.alcaldeConcejal,
                onTap: () => _onMenuAction(_MenuSection.alcaldeConcejal),
              ),
              const SizedBox(width: 36),
              _NavButton(
                icon: Icons.account_balance_outlined,
                label: 'Gobernador',
                selected: _section == _MenuSection.gobernadorAsambleista,
                onTap: () => _onMenuAction(_MenuSection.gobernadorAsambleista),
              ),
              _NavButton(
                icon: Icons.logout,
                label: 'Salir',
                selected: false,
                onTap: _goToLogin,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final Future<void> Function() onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? Colors.cyan : Colors.grey.shade600;
    return InkWell(
      onTap: onTap,
      child: SizedBox(
        width: 76,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 12,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
