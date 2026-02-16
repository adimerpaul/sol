import 'package:flutter/material.dart';

class AuthCheckView extends StatefulWidget {
  const AuthCheckView({super.key});

  @override
  State<AuthCheckView> createState() => _AuthCheckViewState();
}

class _AuthCheckViewState extends State<AuthCheckView> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkAuth();
    });
  }
  Future<void> _checkAuth() async {
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }
  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }
}
