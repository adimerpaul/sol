import 'dart:async';

import 'package:flutter/material.dart';

import '../services/mobile_auth_local_store.dart';
import '../services/mobile_sync_service.dart';
import 'pages/alcalde_concejal_page.dart';
import 'pages/gobernador_asambleista_page.dart';
import 'pages/mapa_page.dart';
import 'pages/perfil_page.dart';

enum _MenuSection { perfil, asistencia, mapa, votacion }

class MenuView extends StatefulWidget {
  const MenuView({super.key});

  @override
  State<MenuView> createState() => _MenuViewState();
}

class _MenuViewState extends State<MenuView> {
  _MenuSection _section = _MenuSection.perfil;
  final MobileSyncService _syncService = MobileSyncService();
  bool _syncing = false;
  int _pendingSyncCount = 0;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _refreshPendingSync();
    _refreshTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      _refreshPendingSync();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _goToLogin() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Salir'),
        content: const Text('¿Estás seguro que vas a salir?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Salir'),
          ),
        ],
      ),
    );
    if (confirm != true) return;

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
    await _refreshPendingSync();
  }

  Future<void> _refreshPendingSync() async {
    final count = await MobileAuthLocalStore.instance.getPendingSyncCount();
    if (!mounted) return;
    setState(() => _pendingSyncCount = count);
  }

  Future<void> _syncNow() async {
    if (_syncing) return;
    setState(() => _syncing = true);
    try {
      final result = await _syncService.syncAll();
      if (!mounted) return;
      setState(() => _pendingSyncCount = result.pendingCount);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Sincronizados: asistencia ${result.asistenciaEnviados}, votacion ${result.votacionEnviados}',
          ),
          duration: const Duration(seconds: 3),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo sincronizar. Sigue en modo offline.'),
          duration: Duration(seconds: 3),
        ),
      );
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  Widget _currentPage() {
    switch (_section) {
      case _MenuSection.perfil:
        return const PerfilPage();
      case _MenuSection.asistencia:
        return const GobernadorAsambleistaPage();
      case _MenuSection.mapa:
        return const MapaPage();
      case _MenuSection.votacion:
        return const AlcaldeConcejalPage();
    }
  }

  String _title() {
    switch (_section) {
      case _MenuSection.perfil:
        return 'Perfil';
      case _MenuSection.asistencia:
        return 'Asistencia';
      case _MenuSection.mapa:
        return 'Mapa';
      case _MenuSection.votacion:
        return 'Subir votacion';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_title()),
        actions: [
          if (_pendingSyncCount > 0)
            FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: Colors.red.shade700,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              ),
              onPressed: _syncing ? null : _syncNow,
              icon: const Icon(Icons.sync_problem),
              label: Text(
                _syncing
                    ? 'Sincronizando...'
                    : 'Sincronizar ($_pendingSyncCount)',
              ),
            ),
          PopupMenuButton<String>(
            onSelected: (value) async {
              if (value == 'perfil') {
                await _onMenuAction(_MenuSection.perfil);
              } else if (value == 'asistencia') {
                await _onMenuAction(_MenuSection.asistencia);
              } else if (value == 'mapa') {
                await _onMenuAction(_MenuSection.mapa);
              } else if (value == 'votacion') {
                await _onMenuAction(_MenuSection.votacion);
              } else if (value == 'salir') {
                await _goToLogin();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'perfil', child: Text('Perfil')),
              PopupMenuItem(value: 'asistencia', child: Text('Asistencia')),
              PopupMenuItem(value: 'mapa', child: Text('Mapa')),
              PopupMenuItem(value: 'votacion', child: Text('Subir votacion')),
              PopupMenuItem(value: 'salir', child: Text('Salir')),
            ],
          ),
        ],
      ),
      body: _currentPage(),
      floatingActionButton: FloatingActionButton(
        heroTag: 'go_map_fab',
        onPressed: () => _onMenuAction(_MenuSection.mapa),
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
                icon: Icons.account_balance_outlined,
                label: 'Asistencia',
                selected: _section == _MenuSection.asistencia,
                onTap: () => _onMenuAction(_MenuSection.asistencia),
              ),
              const SizedBox(width: 36),
              _NavButton(
                icon: Icons.how_to_vote_outlined,
                label: 'Votacion',
                selected: _section == _MenuSection.votacion,
                onTap: () => _onMenuAction(_MenuSection.votacion),
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
