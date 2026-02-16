import 'package:flutter/foundation.dart';

import '../models/mobile_login_response.dart';
import '../services/mobile_auth_service.dart';

class LoginViewModel extends ChangeNotifier {
  LoginViewModel({MobileAuthService? authService})
      : _authService = authService ?? MobileAuthService();

  final MobileAuthService _authService;

  bool _isLoading = false;
  String? _error;
  MobileLoginResponse? _result;

  bool get isLoading => _isLoading;
  String? get error => _error;
  MobileLoginResponse? get result => _result;

  Future<void> login({
    required String ci,
    required String fechaNacimiento,
  }) async {
    _setState(isLoading: true, error: null, result: null);
    try {
      final result = await _authService.login(
        ci: ci,
        fechaNacimiento: fechaNacimiento,
      );
      _setState(isLoading: false, error: null, result: result);
    } catch (e) {
      _setState(isLoading: false, error: e.toString(), result: null);
    }
  }

  void _setState({
    required bool isLoading,
    required String? error,
    required MobileLoginResponse? result,
  }) {
    _isLoading = isLoading;
    _error = error;
    _result = result;
    notifyListeners();
  }
}
