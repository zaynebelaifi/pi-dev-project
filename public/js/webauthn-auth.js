(function () {
  function b64UrlToUint8(value) {
    var base64 = value.replace(/-/g, '+').replace(/_/g, '/');
    var padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4);
    var raw = atob(padded);
    return Uint8Array.from(raw, function (char) { return char.charCodeAt(0); });
  }

  function uint8ToB64Url(bytes) {
    var binary = String.fromCharCode.apply(null, Array.from(new Uint8Array(bytes)));
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  function parseRequestOptions(options) {
    var parsed = Object.assign({}, options);
    parsed.challenge = b64UrlToUint8(options.challenge);

    if (Array.isArray(options.allowCredentials)) {
      parsed.allowCredentials = options.allowCredentials.map(function (credential) {
        return Object.assign({}, credential, { id: b64UrlToUint8(credential.id) });
      });
    }

    return parsed;
  }

  function toAssertionPayload(assertion) {
    return {
      id: assertion.id,
      type: assertion.type,
      rawId: uint8ToB64Url(assertion.rawId),
      response: {
        authenticatorData: uint8ToB64Url(assertion.response.authenticatorData),
        clientDataJSON: uint8ToB64Url(assertion.response.clientDataJSON),
        signature: uint8ToB64Url(assertion.response.signature),
        userHandle: assertion.response.userHandle ? uint8ToB64Url(assertion.response.userHandle) : null
      },
      clientExtensionResults: assertion.getClientExtensionResults()
    };
  }

  function parseErrorMessage(response, fallbackMessage) {
    return response.json()
      .then(function (payload) {
        if (payload && typeof payload.errorMessage === 'string' && payload.errorMessage.trim() !== '') {
          return payload.errorMessage;
        }
        if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
          return payload.message;
        }
        return fallbackMessage;
      })
      .catch(function () { return fallbackMessage; });
  }

  function mapWebauthnError(error) {
    if (error && typeof error === 'object' && 'name' in error) {
      switch (error.name) {
        case 'NotAllowedError':
          return 'Face ID request was cancelled, timed out, or blocked by browser privacy checks. Confirm you are on the same host used during registration (localhost vs 127.0.0.1) and try again.';
        case 'NotSupportedError':
          return 'This browser does not support Face ID on this device. Use email/password instead.';
        case 'SecurityError':
          return 'Secure context required for Face ID. Please use HTTPS.';
        case 'InvalidStateError':
          return 'Face ID credential is not available for this account on this device.';
        case 'AbortError':
          return 'Face ID authentication was interrupted. Please try again.';
        default:
          break;
      }
    }

    return 'Face ID login failed. Use email/password instead.';
  }

  function validateRequestContext(requestOptions) {
    if (!requestOptions || typeof requestOptions !== 'object') {
      return null;
    }

    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
      return 'Face ID requires HTTPS outside localhost/127.0.0.1.';
    }

    var rpId = typeof requestOptions.rpId === 'string' ? requestOptions.rpId.trim().toLowerCase() : '';
    if (rpId !== '') {
      var host = window.location.hostname.toLowerCase();
      if (host !== rpId && !host.endsWith('.' + rpId)) {
        return 'Browser host mismatch for Face ID. Current host is "' + host + '" but RP ID is "' + rpId + '".';
      }
    }

    return null;
  }

  function initFaceIdLogin(config) {
    var loginButton = document.getElementById(config.buttonId);
    var statusNode = document.getElementById(config.statusId);
    var emailInput = document.getElementById(config.emailInputId);

    if (!loginButton || !statusNode) {
      return;
    }

    if (!window.PublicKeyCredential || !navigator.credentials) {
      loginButton.disabled = true;
      statusNode.textContent = 'Face ID is not available in this browser. Use email/password instead.';
      return;
    }

    var setLoading = function (isLoading) {
      loginButton.disabled = isLoading;
      loginButton.textContent = isLoading ? 'Checking Face ID...' : 'Sign In with Face ID';
    };

    loginButton.addEventListener('click', function () {
      statusNode.textContent = '';
      setLoading(true);

      var username = emailInput && emailInput.value ? emailInput.value.trim() : '';
      var startUrl = config.startUrl + (username ? ('?username=' + encodeURIComponent(username)) : '');

      fetch(startUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (optionsResponse) {
          if (!optionsResponse.ok) {
            return parseErrorMessage(optionsResponse, 'Unable to start Face ID login.').then(function (msg) {
              throw new Error(msg);
            });
          }

          return optionsResponse.json();
        })
        .then(function (requestOptions) {
          var contextError = validateRequestContext(requestOptions);
          if (contextError) {
            throw new Error(contextError);
          }

          return navigator.credentials.get({ publicKey: parseRequestOptions(requestOptions) });
        })
        .then(function (assertion) {
          if (!assertion) {
            throw new Error('Face ID was cancelled. Use email/password instead.');
          }

          return fetch(config.completeUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(toAssertionPayload(assertion))
          });
        })
        .then(function (finishResponse) {
          return finishResponse.json().then(function (payload) {
            if (!finishResponse.ok || payload.status !== 'ok') {
              throw new Error(payload.errorMessage || payload.message || 'Authentication failed, try again.');
            }

            window.location.href = payload.redirect || '/dashboard';
          });
        })
        .catch(function (error) {
          statusNode.textContent = mapWebauthnError(error);

          if (error instanceof Error && error.message && (!('name' in error) || error.name === 'Error')) {
            statusNode.textContent = error.message;
          }
        })
        .finally(function () {
          setLoading(false);
        });
    });
  }

  window.Big4WebauthnAuth = {
    init: initFaceIdLogin
  };
})();
