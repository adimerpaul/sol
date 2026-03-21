import 'dart:io';

import 'package:app/views/auth_check_view.dart';
import 'package:app/views/login_view.dart';
import 'package:app/views/menu_view.dart';
import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:flutter_map_tile_caching/flutter_map_tile_caching.dart';
import 'package:permission_handler/permission_handler.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await FMTCObjectBoxBackend().initialise();
  await FMTCStore('mapStore').manage.create();
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
    if (Platform.isAndroid) {
      await Permission.camera.request();
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Jacha',
      theme: ThemeData(primarySwatch: Colors.red),
      routes: {
        '/': (context) => const AuthCheckView(),
        '/login': (context) => const LoginView(),
        '/menu': (context) => const MenuView(),
      },
      initialRoute: '/',
    );
  }
}
