import 'package:flutter/material.dart';

import '../viewmodels/login_view_model.dart';

class LoginView extends StatefulWidget {
  const LoginView({super.key});

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  final _ciController = TextEditingController();
  final _fechaController = TextEditingController();
  final _vm = LoginViewModel();

  @override
  void dispose() {
    _ciController.dispose();
    _fechaController.dispose();
    _vm.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    // ci = '12345678';
    // fechaNacimiento = '2000-01-01';
    _ciController.text = '40000009';
    _fechaController.text = '2000-11-03';
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year - 18, 1, 1),
      firstDate: DateTime(1900, 1, 1),
      lastDate: now,
    );
    if (selected == null) return;
    final yyyy = selected.year.toString().padLeft(4, '0');
    final mm = selected.month.toString().padLeft(2, '0');
    final dd = selected.day.toString().padLeft(2, '0');
    _fechaController.text = '$yyyy-$mm-$dd';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0A2A66), Color(0xFF0E3A8A), Color(0xFFE9EEF8)],
            stops: [0.0, 0.45, 1.0],
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
                    child: Card(
                      elevation: 8,
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
                                  'assets/images/logo.png',
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
                              ),
                              keyboardType: TextInputType.text,
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _fechaController,
                              readOnly: true,
                              onTap: _pickDate,
                              decoration: _inputDecoration(
                                label: 'Fecha de nacimiento',
                                icon: Icons.calendar_month_outlined,
                              ).copyWith(hintText: 'YYYY-MM-DD'),
                            ),
                            const SizedBox(height: 16),
                            FilledButton(
                              style: FilledButton.styleFrom(
                                backgroundColor: const Color(0xFF0B5ED7),
                                foregroundColor: Colors.white,
                                padding:
                                    const EdgeInsets.symmetric(vertical: 14),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                              ),
                              onPressed: _vm.isLoading
                                  ? null
                                  : () async {
                                      final ci = _ciController.text.trim();
                                      final fecha = _fechaController.text.trim();
                                      if (ci.isEmpty || fecha.isEmpty) {
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
    required IconData icon,
  }) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, color: const Color(0xFF1C4CA3)),
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
}
