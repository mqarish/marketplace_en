import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.marketplace.app',
  appName: 'Marketplace',
  webDir: 'build',
  server: {
    androidScheme: 'https'
  }
};

export default config;
