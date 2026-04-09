import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../services/manual_pdf_service.dart';
import '../viewmodels/login_view_model.dart';

class LoginView extends StatefulWidget {
  const LoginView({super.key});

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  final _ciController = TextEditingController();
  final _vm = LoginViewModel();
  int? _selectedDay;
  int? _selectedMonth;
  int? _selectedYear;

  @override
  void dispose() {
    _ciController.dispose();
    _vm.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
  }

  String? get _fechaNacimiento {
    if (_selectedDay == null || _selectedMonth == null || _selectedYear == null) {
      return null;
    }
    final yyyy = _selectedYear.toString().padLeft(4, '0');
    final mm = _selectedMonth.toString().padLeft(2, '0');
    final dd = _selectedDay.toString().padLeft(2, '0');
    return '$yyyy-$mm-$dd';
  }

  Future<void> _openManual({
    required String assetPath,
    required String fileName,
    required String successLabel,
  }) async {
    try {
      await ManualPdfService.saveAndOpenManual(
        assetPath: assetPath,
        fileName: fileName,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$successLabel guardado en Documentos/Jacha')),
      );
    } on PlatformException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.message ?? 'No se pudo abrir el manual'),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo abrir el manual'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF7A0012), Color(0xFFC5162E), Color(0xFFFFE2E5)],
            stops: [0.0, 0.42, 1.0],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: AnimatedBuilder(
                animation: _vm,
                builder: (context, _) {
                  return ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 430),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: _ManualButton(
                                title: 'Manual de la\nApp Jacha',
                                onTap: () => _openManual(
                                  assetPath: 'assets/aplicacion.pdf',
                                  fileName: 'manual_app_jacha.pdf',
                                  successLabel: 'Manual de la App Jacha',
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _ManualButton(
                                title: 'Manual de\nProceso del Voto',
                                onTap: () => _openManual(
                                  assetPath: 'assets/proceso voto.pdf',
                                  fileName: 'manual_proceso_del_voto.pdf',
                                  successLabel: 'Manual de Proceso del Voto',
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Card(
                          elevation: 10,
                          shadowColor: const Color(0x40220000),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(22),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(20, 26, 20, 20),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                Center(
                                  child: Container(
                                    width: 92,
                                    height: 92,
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      borderRadius: BorderRadius.circular(22),
                                      boxShadow: const [
                                        BoxShadow(
                                          color: Color(0x1A000000),
                                          blurRadius: 14,
                                          offset: Offset(0, 8),
                                        ),
                                      ],
                                    ),
                                    padding: const EdgeInsets.all(12),
                                    child: Image.asset(
                                      'assets/logo.png',
                                      fit: BoxFit.contain,
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 16),
                                const Text(
                                  'Bienvenido',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFF0B1F4A),
                                  ),
                                ),
                                const SizedBox(height: 6),
                                const Text(
                                  'Inicia sesion para continuar',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Color(0xFF5A6680),
                                  ),
                                ),
                                const SizedBox(height: 22),
                                TextField(
                                  controller: _ciController,
                                  decoration: _inputDecoration(
                                    label: 'CI',
                                    icon: Icons.badge_outlined,
                                    hint: 'Ingresa tu carnet',
                                  ),
                                  keyboardType: TextInputType.text,
                                ),
                                const SizedBox(height: 12),
                                _buildFechaNacimientoSelectors(),
                                const SizedBox(height: 16),
                                FilledButton(
                                  style: FilledButton.styleFrom(
                                    backgroundColor: const Color(0xFFF53333),
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 14),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                    ),
                                  ),
                                  onPressed: _vm.isLoading
                                      ? null
                                      : () async {
                                          final ci = _ciController.text.trim();
                                          final fecha = _fechaNacimiento;
                                          if (ci.isEmpty || fecha == null) {
                                            ScaffoldMessenger.of(
                                              this.context,
                                            ).showSnackBar(
                                              const SnackBar(
                                                content: Text(
                                                  'Completa CI y fecha de nacimiento',
                                                ),
                                              ),
                                            );
                                            return;
                                          }
                                          await _vm.login(
                                            ci: ci,
                                            fechaNacimiento: fecha,
                                          );
                                          if (!mounted) return;
                                          if (_vm.result != null) {
                                            Navigator.pushReplacementNamed(
                                              this.context,
                                              '/menu',
                                            );
                                          }
                                        },
                                  child: _vm.isLoading
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            color: Colors.white,
                                          ),
                                        )
                                      : const Text(
                                          'Ingresar',
                                          style: TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                ),
                                if (_vm.error != null) ...[
                                  const SizedBox(height: 12),
                                  Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFFEBEE),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(
                                        color: const Color(0xFFEF9A9A),
                                      ),
                                    ),
                                    child: Text(
                                      _vm.error!,
                                      style: const TextStyle(
                                        color: Color(0xFFB71C1C),
                                        fontSize: 13,
                                      ),
                                    ),
                                  ),
                                ],
                                if (_vm.isOfflineSession) ...[
                                  const SizedBox(height: 12),
                                  Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFFF8E1),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(
                                        color: const Color(0xFFFFE082),
                                      ),
                                    ),
                                    child: const Text(
                                      'Sesion cargada desde SQLite (modo offline)',
                                      style: TextStyle(
                                        color: Color(0xFF8D6E00),
                                        fontWeight: FontWeight.w600,
                                        fontSize: 13,
                                      ),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration({
    required String label,
    IconData? icon,
    String? hint,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      isDense: true,
      prefixIcon: icon != null
          ? Icon(icon, color: const Color(0xFF1C4CA3), size: 18)
          : null,
      filled: true,
      fillColor: const Color(0xFFF7F9FD),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFD6DEEC)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFFD6DEEC)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF0B5ED7), width: 1.6),
      ),
    );
  }

  Widget _buildFechaNacimientoSelectors() {
    final dayItems = List.generate(31, (i) => i + 1)
        .map(
          (d) => DropdownMenuItem<int>(
            value: d,
            child: Text(d.toString().padLeft(2, '0')),
          ),
        )
        .toList();

    final monthItems = List.generate(12, (i) => i + 1)
        .map(
          (m) => DropdownMenuItem<int>(
            value: m,
            child: Text(m.toString().padLeft(2, '0')),
          ),
        )
        .toList();

    final yearItems = List.generate(
      DateTime.now().year - 1900 + 1,
      (i) => DateTime.now().year - i,
    )
        .map(
          (y) => DropdownMenuItem<int>(
            value: y,
            child: Text(y.toString()),
          ),
        )
        .toList();

    final dayField = DropdownButtonFormField<int>(
      value: _selectedDay,
      isExpanded: true,
      decoration: _inputDecoration(label: 'Dia', hint: 'Selecciona'),
      items: dayItems,
      onChanged: _vm.isLoading ? null : (v) => setState(() => _selectedDay = v),
    );

    final monthField = DropdownButtonFormField<int>(
      value: _selectedMonth,
      isExpanded: true,
      decoration: _inputDecoration(label: 'Mes', hint: 'Selecciona'),
      items: monthItems,
      onChanged: _vm.isLoading
          ? null
          : (v) => setState(() => _selectedMonth = v),
    );

    final yearField = DropdownButtonFormField<int>(
      value: _selectedYear,
      isExpanded: true,
      decoration: _inputDecoration(label: 'Ano', hint: 'Selecciona'),
      items: yearItems,
      onChanged: _vm.isLoading ? null : (v) => setState(() => _selectedYear = v),
    );

    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth < 370) {
          return Column(
            children: [
              Row(
                children: [
                  Expanded(child: dayField),
                  const SizedBox(width: 8),
                  Expanded(child: monthField),
                ],
              ),
              const SizedBox(height: 8),
              yearField,
            ],
          );
        }

        return Row(
          children: [
            Expanded(child: dayField),
            const SizedBox(width: 8),
            Expanded(child: monthField),
            const SizedBox(width: 8),
            Expanded(child: yearField),
          ],
        );
      },
    );
  }
}

class _ManualButton extends StatelessWidget {
  const _ManualButton({
    required this.title,
    required this.onTap,
  });

  final String title;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      elevation: 4,
      shadowColor: const Color(0x33000000),
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
          child: Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: Color(0xFF181818),
              height: 1.2,
            ),
          ),
        ),
      ),
    );
  }
}
