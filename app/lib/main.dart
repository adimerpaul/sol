import 'dart:io';

import 'package:app/views/auth_check_view.dart';
import 'package:app/views/login_view.dart';
import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:permission_handler/permission_handler.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  const isRelease = bool.fromEnvironment('dart.vm.product');
  await dotenv.load(fileName: isRelease ? '.env.production' : '.env');
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _requestPermissions();
    });
  }

  Future<void> _requestPermissions() async {
    await Permission.location.request();
    // await Permission.notification.request();
    // pedir fotos y videos
    // await Permission.photos.request();
    if (Platform.isAndroid) {
      await Permission.camera.request();
      // await Permission.storage.request();
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Jacha',
      theme: ThemeData(
        primarySwatch: Colors.cyan,
      ),
      routes: {
        '/': (context) => const AuthCheckView(),
        '/login': (context) => const LoginView(),
      },
      initialRoute: '/',
    );
  }
}
