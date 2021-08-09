var Generator = require('yeoman-generator');
const through = require('through2');
var prettier = require('prettier');
var pluralize = require('pluralize');
var _ = require('lodash');

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
  entityNameInput;
  parentFolderInput;
  fieldPath;
  fields = [];
  relations = [];
  parentFolder;
  capEntityName;
  snakeEntityName; //dashed(-) snake entity name
  camelEntityName;
  camelPluralEntityName;
  entityName;

  constructor(args, opts) {
    super(args, opts);
    this.registerTransformStream(prettierTransform({}, this, true));
  }

  async prompting() {
    await this._getEntityName();
    await this._getParentFolderName();
    await this._getFields();
  }

  async _getEntityName() {
    this.entityNameInput = await this.prompt([
      {
        type: 'input',
        name: 'name',
        message: 'What is the name of the ENTITY?',
      },
    ]);
    if (this.entityNameInput.name === undefined) {
      this.log('invalid ENTITY name !!');
      await this._getEntityName();
    } else if (this.entityNameInput.name.includes(' ')) {
      await this._getEntityName();
    }
  }

  async _getParentFolderName() {
    this.parentFolderInput = await this.prompt([
      {
        type: 'list',
        name: 'name',
        message: 'Select which module this entity belongs to? ',
        choices: [
          {
            name: 'Setup',
            value: 'setup',
          },
          {
            name: 'Planning',
            value: 'planning',
          },
          {
            name: 'Execution',
            value: 'execution',
          },
        ],
      },
    ]);
    if (this.parentFolderInput.name === undefined) {
      this.log('invalid PARENT folder name');
      await this._getParentFolderName();
    } else if (this.parentFolderInput.name.includes(' ')) {
      await this._getParentFolderName();
    }
  }

  async _getFields() {
    this.path = await this.prompt([
      {
        type: 'input',
        name: 'uri',
        message: 'Please specify path containing FIELDS:',
      },
    ]);
    let data = await this.fs.readJSON(this.destinationPath('.entities/' + this.path.uri));
    if (data == undefined) {
      this.log('invalid path please try again');
      await this._getFields();
    } else {
      this.fields = data;
    }
  }

  writing() {
    this._init();
    this._generateModule();
    this._generateRouteModule();
    this._generateModel();
    this._generateService();
    this._generateComponent();
    this._generateComponentUpdate();
    this._updateRoute();
  }

  _init() {
    this.parentFolder = this.parentFolderInput.name.toLowerCase();
    const e = this.entityNameInput.name;
    this.capEntityName = e.charAt(0).toUpperCase() + e.slice(1);
    this.camelEntityName = this.capEntityName.charAt(0).toLowerCase() + e.slice(1);
    this.camelPluralEntityName = pluralize(this.camelEntityName);
    this.entityName = _.startCase(this.capEntityName);
    this.snakeEntityName = this.capEntityName.replace(/[A-Z]/g, (letter, index) => {
      return index == 0 ? letter.toLowerCase() : '-' + letter.toLowerCase();
    });
    this.fields = this.fields.map(f => {
      return {
        ...f,
        header: _.startCase(f.name).replace('Id', ''),
      };
    });

    const relations = this.fields.filter(f => f.relation != undefined);
    this.relations = relations.map(r => {
      const props = r.relation.split(',');
      const type = props[0];
      const rlCap = props[1];
      const rlCamel = rlCap.charAt(0).toLowerCase() + rlCap.slice(1);
      const rlSnake = rlCap.replace(/[A-Z]/g, (letter, index) => {
        return index == 0 ? letter.toLowerCase() : '-' + letter.toLowerCase();
      });
      const rlCamelPlural = pluralize(rlCamel);

      return {
        ...r,
        rlCap,
        type,
        rlCamel,
        rlSnake,
        rlCamelPlural,
      };
    });
  }

  _generateModule() {
    /**
     * Generate module file
     */
    this.fs.copyTpl(
      this.templatePath('module.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.module.ts`),
      { capEntityName: this.capEntityName, snakeEntityName: this.snakeEntityName }
    );
  }

  _generateRouteModule() {
    /**
     * Generate module file
     */
    this.fs.copyTpl(
      this.templatePath('routing.module.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}-routing.module.ts`),
      { capEntityName: this.capEntityName, snakeEntityName: this.snakeEntityName }
    );
  }

  _generateModel() {
    /**
     * Generate module file
     */
    this.fs.copyTpl(
      this.templatePath('model.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.model.ts`),
      { capEntityName: this.capEntityName, snakeEntityName: this.snakeEntityName, fields: this.fields }
    );
  }

  _generateService() {
    this.fs.copyTpl(
      this.templatePath('service.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.service.ts`),
      { capEntityName: this.capEntityName, snakeEntityName: this.snakeEntityName, camelEntityName: this.camelEntityName }
    );
  }

  _generateComponent() {
    this.fs.copyTpl(
      this.templatePath('component.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.component.ts`),
      {
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        entityName: this.entityName,
      }
    );
    this.fs.copyTpl(
      this.templatePath('component.html.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.component.html`),
      {
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        entityName: this.entityName,
      }
    );
  }

  _generateComponentUpdate() {
    this.fs.copyTpl(
      this.templatePath('component-update.ts.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/update/${this.snakeEntityName}-update.component.ts`),
      {
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        relations: this.relations,
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        entityName: this.entityName,
      }
    );
    this.fs.copyTpl(
      this.templatePath('component-update.html.ejs'),
      this.destinationPath(`src/app/${this.parentFolder}/${this.snakeEntityName}/update/${this.snakeEntityName}-update.component.html`),
      {
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        relations: this.relations,
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        entityName: this.entityName,
      }
    );
  }

  async _updateRoute() {
    const path = this.destinationPath('src/app/layout/layout-routing.module.ts');
    let file = this.fs.read(path);
    const hook = '/**====Planrep router Generator Hook: Dont Delete====*/';
    const route = `{
      path: '${this.snakeEntityName}',
      loadChildren: () =>
        import('../${this.parentFolder}/${this.snakeEntityName}/${this.snakeEntityName}.module').then(
          (m) => m.${this.capEntityName}Module
        ),
    },\n`;
    if (!file.includes(route)) {
      const insert = route + hook;
      this.fs.write(path, file.replace(hook, insert));
    } else {
      this.log('Route exist, skipping');
    }
  }
};
