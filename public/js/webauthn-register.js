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

  function parseCreationOptions(options) {
    var parsed = Object.assign({}, options);
    parsed.challenge = b64UrlToUint8(options.challenge);
    parsed.user = Object.assign({}, options.user, {
      id: b64UrlToUint8(options.user.id)
    });

    if (Array.isArray(options.excludeCredentials)) {
      parsed.excludeCredentials = options.excludeCredentials.map(function (credential) {
        return Object.assign({}, credential, { id: b64UrlToUint8(credential.id) });
      });
    }

    return parsed;
  }

  function toAttestationPayload(credential) {
    return {
      id: credential.id,
      type: credential.type,
      rawId: uint8ToB64Url(credential.rawId),
      response: {
        clientDataJSON: uint8ToB64Url(credential.response.clientDataJSON),
        attestationObject: uint8ToB64Url(credential.response.attestationObject),
        transports: credential.response.getTransports ? credential.response.getTransports() : []
      },
      clientExtensionResults: credential.getClientExtensionResults()
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
          return 'Face ID enrollment was cancelled, timed out, or blocked by browser privacy checks. Verify host consistency (localhost vs 127.0.0.1) and retry.';
        case 'NotSupportedError':
          return 'This browser/device does not support Face ID enrollment.';
        case 'SecurityError':
          return 'Secure context required for Face ID enrollment. Please use HTTPS.';
        case 'InvalidStateError':
          return 'Face ID is already registered on this device for this account.';
        case 'AbortError':
          return 'Face ID enrollment was interrupted. Please try again.';
        default:
          break;
      }
    }

    return 'Face ID registration failed.';
  }

  function validateCreationContext(creationOptions) {
    if (!creationOptions || typeof creationOptions !== 'object') {
      return null;
    }

    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
      return 'Face ID requires HTTPS outside localhost/127.0.0.1.';
    }

    var rpId = '';
    if (creationOptions.rp && typeof creationOptions.rp.id === 'string') {
      rpId = creationOptions.rp.id.trim().toLowerCase();
    }

    if (rpId !== '') {
      var host = window.location.hostname.toLowerCase();
      if (host !== rpId && !host.endsWith('.' + rpId)) {
        return 'Browser host mismatch for Face ID. Current host is "' + host + '" but RP ID is "' + rpId + '".';
      }
    }

    return null;
  }

  function initFaceIdRegister(config) {
    var registerButton = document.getElementById(config.buttonId);
    var statusNode = document.getElementById(config.statusId);

    if (!registerButton || !statusNode) {
      return;
    }

    if (!window.PublicKeyCredential || !navigator.credentials) {
      registerButton.disabled = true;
      statusNode.textContent = 'Face ID is not available in this browser.';
      return;
    }

    var setLoading = function (isLoading) {
      registerButton.disabled = isLoading;
      registerButton.textContent = isLoading ? 'Registering Face ID...' : 'Enable Face ID on this device';
    };

    registerButton.addEventListener('click', function () {
      statusNode.textContent = '';
      setLoading(true);

      fetch(config.startUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (optionsResponse) {
          if (!optionsResponse.ok) {
            return parseErrorMessage(optionsResponse, 'Unable to start Face ID registration.').then(function (msg) {
              throw new Error(msg);
            });
          }

          return optionsResponse.json();
        })
        .then(function (creationOptions) {
          var contextError = validateCreationContext(creationOptions);
          if (contextError) {
            throw new Error(contextError);
          }

          return navigator.credentials.create({ publicKey: parseCreationOptions(creationOptions) });
        })
        .then(function (credential) {
          if (!credential) {
            throw new Error('Face ID registration was cancelled.');
          }

          return fetch(config.completeUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(toAttestationPayload(credential))
          });
        })
        .then(function (finishResponse) {
          return finishResponse.json().then(function (payload) {
            if (!finishResponse.ok || payload.status !== 'ok') {
              throw new Error(payload.errorMessage || payload.message || 'Unable to complete Face ID registration.');
            }

            statusNode.textContent = 'Face ID successfully enabled for this account.';
            statusNode.style.color = '#1f5e3b';
          });
        })
        .catch(function (error) {
          statusNode.textContent = mapWebauthnError(error);

          if (error instanceof Error && error.message && (!('name' in error) || error.name === 'Error')) {
            statusNode.textContent = error.message;
          }
          statusNode.style.color = '#842029';
        })
        .finally(function () {
          setLoading(false);
        });
    });
  }

  window.Big4WebauthnRegister = {
    init: initFaceIdRegister
  };
})();
