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
      appBar: AppBar(title: const Text('Login Mobile')),
      body: AnimatedBuilder(
        animation: _vm,
        builder: (context, _) {
          final data = _vm.result;
          return Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: _ciController,
                  decoration: const InputDecoration(
                    labelText: 'CI',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.text,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _fechaController,
                  readOnly: true,
                  onTap: _pickDate,
                  decoration: const InputDecoration(
                    labelText: 'Fecha de nacimiento',
                    hintText: 'YYYY-MM-DD',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                ElevatedButton(
                  onPressed: _vm.isLoading
                      ? null
                      : () async {
                          final ci = _ciController.text.trim();
                          final fecha = _fechaController.text.trim();
                          if (ci.isEmpty || fecha.isEmpty) {
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              const SnackBar(
                                content: Text(
                                  'Completa CI y fecha de nacimiento',
                                ),
                              ),
                            );
                            return;
                          }
                          await _vm.login(ci: ci, fechaNacimiento: fecha);
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
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Ingresar'),
                ),
                const SizedBox(height: 16),
                if (_vm.error != null) ...[
                  Text(_vm.error!, style: const TextStyle(color: Colors.red)),
                  const SizedBox(height: 8),
                ],
                if (data != null) ...[
                  if (_vm.isOfflineSession)
                    const Text(
                      'Sesion cargada desde SQLite (modo offline)',
                      style: TextStyle(
                        color: Colors.orange,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  if (_vm.isOfflineSession) const SizedBox(height: 8),
                  const Text(
                    'Resultado:',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text('Token: ${data.token}'),
                  Text('Nombre: ${data.user.name}'),
                  Text('CI: ${data.user.ci}'),
                  if (data.user.role != null) Text('Rol: ${data.user.role}'),
                  Text('Jefes: ${data.jerarquia.jefes.length}'),
                  Text('Supervisores: ${data.jerarquia.supervisores.length}'),
                  Text('Mesas asignadas: ${data.mesas.length}'),
                  if (data.mesas.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    const Text(
                      'Mesas:',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 4),
                    ...data.mesas.map(
                      (mesa) => Text(
                        'Mesa ${mesa.numeroMesa ?? '-'} | ${mesa.estado ?? '-'} | ${mesa.recintoNombre ?? '-'}',
                      ),
                    ),
                  ],
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
