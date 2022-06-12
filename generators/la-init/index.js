var Generator = require('yeoman-generator');
const through = require('through2');
var prettier = require('prettier');
var pluralize = require('pluralize');
var _ = require('lodash');
const fs = require('fs');
const { exec } = require('child_process');
const process = require('process');

const prettierTransform = function (options, generator, ignoreErrors = false) {
  return through.obj((file, encoding, callback) => {
    if (file.state === 'deleted') {
      callback(null, file);
      return;
    }
    /* resolve from the projects config */
    let fileContent;
    prettier
      .resolveConfig(file.relative)
      .then(function (resolvedDestinationFileOptions) {
        const prettierOptions = {
          plugins: [],
          // Config from disk
          ...resolvedDestinationFileOptions,
          // for better errors
          filepath: file.relative,
          parser: 'php',
        };
        if (options.packageJson) {
          prettierOptions.plugins.push(prettierPluginPackagejson);
        }
        if (options.java) {
          prettierOptions.plugins.push(prettierPluginJava);
        }
        fileContent = file.contents.toString('utf8');
        const data = prettier.format(fileContent, prettierOptions);
        file.contents = Buffer.from(data);
        callback(null, file);
      })
      .catch(error => {
        const errorMessage = `Error parsing file ${file.relative}: ${error}

At: ${fileContent}`;
        if (ignoreErrors) {
          generator.log(errorMessage);
          callback(null, file);
        } else {
          callback(new Error(errorMessage));
        }
      });
  });
};

module.exports = class extends Generator {
  appParams;
  authParams;
  appfolder;

  constructor(args, opts) {
    super(args, opts);
    this.registerTransformStream(prettierTransform({}, this, true));
  }

  async prompting() {
    await this._getAppParams();
  }

  async _getAppParams() {
    this.appParams = await this.prompt([
      {
        type: 'input',
        name: 'name',
        message: 'What is the name of application?',
      },
    ]);
    if (this.appParams.name === undefined) {
      this.log('Invalid Application name !');
      await this._getAppParams();
    } else if (this.appParams.name.includes(' ')) {
      await this._getAppParams();
    }
  }

  async _getAuthParams() {
    this.authParams = await this.prompt([
      {
        type: 'list',
        name: 'name',
        message: 'Select  Authentication options',
        choices: [
          {
            name: 'Laravel  Sanctum',
            value: 'sanctum',
          },
        ],
      },
    ]);
    if (this.authParams.name === undefined) {
      this.log('Invalid Authentication !');
      await this._getAuthParams();
    } else if (this.authParams.name.includes(' ')) {
      await this._getAuthParams();
    }
  }

  async _getDbParams() {}

  writing() {
    this._init();
    //this._initProject();
    //this._initAuth();
    this._test();
  }

  _init() {
    // Replace all white space with underscore to create folder name
    this.appfolder = 'starter'; // this.appParams.name;
  }

  _initProject() {
    // Run composer command to create laravel app
    this.log(`Running..[composer create-project laravel/laravel ${this.appfolder}]`);
    exec(`composer create-project laravel/laravel ${this.appParams.name}`, (error, stdout, stderr) => {
      this.log(stdout);
    });
  }

  _test() {
    //Copy user migration
    let now = new Date().toISOString();
    now = now.replace('-', '_').replace('-', '_').replace(':', '').replace(':', '').replace('T', '_').substring(0, 17);
    this.fs.copyTpl(
      this.templatePath('migrations/0000_00_00_initial_migration.php'),
      this.destinationPath(`${this.appfolder}/database/migrations/${now}_initial_migration.php`)
    );

    //copy Model event listener
    this.fs.copyTpl(
      this.templatePath('models/ModelEventObserver.php'),
      this.destinationPath(`${this.appfolder}/app/Models/ModelEventObserver.php`)
    );

    //Copy Audit Base Model
    this.fs.copyTpl(
      this.templatePath('models/AuditingBaseModel.php'),
      this.destinationPath(`${this.appfolder}/app/Models/AuditingBaseModel.php`)
    );

    //create Base Repositories
    this.log('Creating base repository');
    this.fs.copyTpl(
      this.templatePath('repositories/BaseRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/BaseRepository.php`)
    );

    //create Base Api Request
    this.log('Creating api base request');
    this.fs.copyTpl(
      this.templatePath('requests/ApiRequest.php'),
      this.destinationPath(`${this.appfolder}/app/Http/Requests/ApiRequest.php`)
    );

    //create Base Controller
    this.log('Creating api base controller');
    this.fs.copyTpl(
      this.templatePath('controllers/ApiBaseController.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/ApiBaseController.php`)
    );

    //create Base Api Request
    this.log('Creating api route');
    this.fs.copyTpl(this.templatePath('routes/api.php'), this.destinationPath(`${this.appfolder}/routes/api.php`));

    //Copy user Controller
    //Copy user Update api route
  }
  _initAuth() {
    //if authparam.name = secturm
    this._generateSanctum();
  }

  _generateUser() {
    this.log('creating user model');
    this.fs.copyTpl(this.templatePath('models/User.php'), this.destinationPath(`${this.appfolder}/app/Models/Setup/User.php`));

    this.log('creating user repository');
    this.fs.copyTpl(
      this.templatePath('repositories/UserRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/Setup/UserRepository.php`)
    );

    this.log('creating user create request');
    this.fs.copyTpl(
      this.templatePath('requests/CreateUserApiRequest.php'),
      this.destinationPath(`${this.appfolder}/app/Http/Requests/Api/Setup/CreateUserApiRequest.php`)
    );

    this.log('creating user update request');
    this.fs.copyTpl(
      this.templatePath('requests/UpdateUserApiRequest.php'),
      this.destinationPath(`${this.appfolder}/app/Http/Requests/Api/Setup/UpdateUserApiRequest.php`)
    );

    this.log('creating user controller');
    this.fs.copyTpl(
      this.templatePath('controllers/UserApiController.php'),
      this.destinationPath(`${this.appfolder}/app/Http/Controllers/Api/Setup/UserApiController.php`)
    );

    this.log('updating user api routes');
  }

  _generateRole() {
    this.fs.copyTpl(this.templatePath('models/Role.php'), this.destinationPath(`${this.appfolder}/app/Models/Setup/Role.php`));
    this.fs.copyTpl(
      this.templatePath('repositories/RoleRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/Setup/RoleRepository.php`)
    );
  }

  _generateGroup() {
    this.fs.copyTpl(this.templatePath('models/Group.php'), this.destinationPath(`${this.appfolder}/app/Models/Setup/Group.php`));
    this.fs.copyTpl(
      this.templatePath('repositories/GroupRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/Setup/GroupRepository.php`)
    );
  }

  _generateMenu() {
    this.fs.copyTpl(this.templatePath('models/Menu.php'), this.destinationPath(`${this.appfolder}/app/Models/Setup/Menu.php`));
    this.fs.copyTpl(
      this.templatePath('repositories/MenuRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/Setup/MenuRepository.php`)
    );
  }

  _generatePermission() {
    this.fs.copyTpl(this.templatePath('models/Permission.php'), this.destinationPath(`${this.appfolder}/app/Models/Setup/Permission.php`));
    this.fs.copyTpl(
      this.templatePath('repositories/PermissionRepository.php'),
      this.destinationPath(`${this.appfolder}/app/Repositories/Setup/PermissionRepository.php`)
    );
  }

  _generateSanctum() {
    process.chdir(this.appfolder);
    exec(`composer require laravel/sanctum`, (error, stdout, stderr) => {
      this.log(stdout);
    });
    exec(`php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"`, (error, stdout, stderr) => {
      this.log(stdout);
    });
  }

  async _updateRoute(url, module, model) {
    const path = this.destinationPath(`${this.appfolder}/routes/api.php`);
    let file = this.fs.read(path);
    const hook = '//<--routes-insertion-needle-donot-remove-->';
    const route = `Route::resource('${url}', ${module}\\${model}ApiController::class);\n`;

    if (!file.includes(`${module}\\${model}ApiController::class`)) {
      const insert = route + hook;
      this.fs.write(path, file.replace(hook, insert));
    } else {
      this.log('Route exist, skipping');
    }
  }
};
