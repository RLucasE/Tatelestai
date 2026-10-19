import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axiosInstance from './axios';

window.Pusher = Pusher;

let echoInstance = null;

export function getEchoInstance() {
  if (!echoInstance) {
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'ddasnp2gt2fou5tmpwu9';
    const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname || 'localhost';
    const reverbPort = import.meta.env.VITE_REVERB_PORT || 8080;
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';

    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: reverbKey,
      wsHost: reverbHost,
      wsPort: Number(reverbPort),
      wssPort: Number(reverbPort),
      forceTLS: reverbScheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authorizer: (channel) => {
        return {
          authorize: (socketId, callback) => {
            axiosInstance
              .post('/broadcasting/auth', {
                socket_id: socketId,
                channel_name: channel.name,
              })
              .then((response) => {
                callback(null, response.data);
              })
              .catch((error) => {
                console.error('[Echo] Error autorizando canal:', channel.name, error);
                callback(error);
              });
          },
        };
      },
    });
  }

  return echoInstance;
}

export function disconnectEcho() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}
