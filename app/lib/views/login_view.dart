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
      appBar: AppBar(
        title: const Text('Login Mobile'),
      ),
      body: AnimatedBuilder(
        animation: _vm,
        builder: (context, _) {
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
                      : () {
                          final ci = _ciController.text.trim();
                          final fecha = _fechaController.text.trim();
                          if (ci.isEmpty || fecha.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content:
                                    Text('Completa CI y fecha de nacimiento'),
                              ),
                            );
                            return;
                          }
                          _vm.login(ci: ci, fechaNacimiento: fecha);
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
                  Text(
                    _vm.error!,
                    style: const TextStyle(color: Colors.red),
                  ),
                  const SizedBox(height: 8),
                ],
                if (_vm.result != null) ...[
                  const Text(
                    'Resultado:',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text('Nombre: ${_vm.result!.userName}'),
                  Text('CI: ${_vm.result!.userCi}'),
                  if (_vm.result!.userRole != null)
                    Text('Rol: ${_vm.result!.userRole}'),
                  if (_vm.result!.mesaNumero != null)
                    Text('Mesa: ${_vm.result!.mesaNumero}'),
                  if (_vm.result!.mesaEstado != null)
                    Text('Estado mesa: ${_vm.result!.mesaEstado}'),
                  if (_vm.result!.recintoNombre != null)
                    Text('Recinto: ${_vm.result!.recintoNombre}'),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
