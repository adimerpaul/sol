import 'package:flutter/foundation.dart';

import '../models/mobile_login_response.dart';
import '../services/mobile_auth_service.dart';
import '../services/mobile_auth_local_store.dart';

class LoginViewModel extends ChangeNotifier {
  LoginViewModel({
    MobileAuthService? authService,
    MobileAuthLocalStore? localStore,
  }) : _authService = authService ?? MobileAuthService(),
       _localStore = localStore ?? MobileAuthLocalStore.instance;

  final MobileAuthService _authService;
  final MobileAuthLocalStore _localStore;

  bool _isLoading = false;
  String? _error;
  MobileLoginResponse? _result;
  bool _isOfflineSession = false;

  bool get isLoading => _isLoading;
  String? get error => _error;
  MobileLoginResponse? get result => _result;
  bool get isOfflineSession => _isOfflineSession;

  Future<void> login({
    required String ci,
    required String fechaNacimiento,
  }) async {
    _isLoading = true;
    _error = null;
    _result = null;
    _isOfflineSession = false;
    notifyListeners();
    try {
      _result = await _authService.login(
        ci: ci,
        fechaNacimiento: fechaNacimiento,
      );
      await _localStore.saveLogin(_result!);
    } catch (e) {
      final localResult = await _localStore.readLoginForUser(
        ci: ci,
        fechaNacimiento: fechaNacimiento,
      );
      if (localResult != null) {
        _result = localResult;
        _isOfflineSession = true;
        _error = null;
      } else {
        _error = e.toString();
        _result = null;
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
