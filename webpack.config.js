const Encore = require('@symfony/webpack-encore');

// Ensure the runtime environment is configured (required for Encore)
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'development');
}

Encore
  // write compiled assets to the standard Encore build directory expected by Twig
  .setOutputPath('public/build/')
  // the public path used by the web server to access the output path
  .setPublicPath('/build')

  // JS entries
  .addEntry('app', './assets/app.js')
  .addEntry('admin', './assets/admin.js')

  // CSS will be imported from JS entries (keeps entries simple)

  // Enable Stimulus bridge if controllers.json exists
  .enableStimulusBridge('./assets/controllers.json')

  // Optimize and split vendor files
  .splitEntryChunks()

  // Use single runtime chunk (recommended)
  .enableSingleRuntimeChunk()

  // Clean the output dir before build
  .cleanupOutputBeforeBuild()

  // Enable source maps in non-production for easier debugging
  .enableSourceMaps(!Encore.isProduction())

  // Extract CSS into separate files (enabled by default for production)
  .enablePostCssLoader()

  // Enable versioning (content-hash) in production for long-term caching
  .enableVersioning(Encore.isProduction())
  ;

module.exports = Encore.getWebpackConfig();
