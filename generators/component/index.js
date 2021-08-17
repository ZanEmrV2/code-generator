var Generator = require('yeoman-generator');
const through = require('through2');
var prettier = require('prettier');
var pluralize = require('pluralize');
var _ = require('lodash');
const fs = require('fs');

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
  rawRelations = [];
  mandatory = [];
  parentFolder;
  capEntityName;
  snakeEntityName; //dashed(-) snake entity name
  camelEntityName;
  camelPluralEntityName;
  entityName;
  resourcePath;

  constructor(args, opts) {
    super(args, opts);
    this.registerTransformStream(prettierTransform({}, this, true));
  }

  async prompting() {
    await this._getEntityName();
    await this._getParentFolderName();
    await this._getFields();
    await this._getMandator();
  }

  async _getEntityName() {
    //Read entities from
    const entityOptions = [];
    fs.readdirSync('.entities').forEach(file => {
      entityOptions.push({
        name: file.replace('.json', ''),
        value: file,
      });
    });
    //If entityOptions empty exit

    this.entityNameInput = await this.prompt([
      {
        type: 'list',
        name: 'name',
        message: 'Select entity?',
        choices: entityOptions,
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
    let data = await this.fs.readJSON(this.destinationPath('.entities/' + this.entityNameInput.name));
    if (data == undefined) {
      this.log('invalid path ');
    } else {
      this.fields = data.filter(f => {
        return !f.name.includes('created') && !f.name.includes('updated') && f.dbType !== 'timestamp';
      });
    }
    this.rawRelations = this.fields.filter(f => f.relation != undefined);
  }

  async _getMandator() {
    this.log(this.rawRelations);
    const choices = this.rawRelations.map(r => {
      return {
        name: r.name.replace('_id', ''),
        value: r.name,
      };
    });
    this.log(choices);
    this.mandatory = await this.prompt([
      {
        type: 'checkbox',
        name: 'filters',
        message: 'Select Mandatory Filters',
        choices,
      },
    ]);
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
    const e = this.entityNameInput.name.replace('.json', '').replace('_', '').replace('-', '');
    this.capEntityName = e.charAt(0).toUpperCase() + e.slice(1);
    this.camelEntityName = this.capEntityName.charAt(0).toLowerCase() + e.slice(1);
    this.camelPluralEntityName = pluralize(this.camelEntityName);
    this.entityName = _.startCase(this.capEntityName);
    this.snakeEntityName = this.capEntityName.replace(/[A-Z]/g, (letter, index) => {
      return index == 0 ? letter.toLowerCase() : '-' + letter.toLowerCase();
    });
    const _resourcePath = this.capEntityName.replace(/[A-Z]/g, (letter, index) => {
      return index == 0 ? letter.toLowerCase() : '_' + letter.toLowerCase();
    });
    this.resourcePath = pluralize(_resourcePath);
    this.fields = this.fields.map(f => {
      return {
        ...f,
        header: _.startCase(f.name).replace('Id', ''),
      };
    });

    this.relations = this.rawRelations.map(r => {
      const props = r.relation.split(',');
      const type = props[0];
      let rlCap = '';
      let n = r.name.replace('_id', '');
      this.log(n);
      let nArray = n.split('_');
      this.log(nArray);
      nArray.forEach(w => (rlCap = rlCap + w.charAt(0).toUpperCase() + w.slice(1)));
      this.log(rlCap);
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
    this.log(this.mandatory);
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
      {
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        camelEntityName: this.camelEntityName,
        resourcePath: this.resourcePath,
      }
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
        relations: this.relations,
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
        relations: this.relations,
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
    if (!file.includes(this.snakeEntityName)) {
      const insert = route + hook;
      this.fs.write(path, file.replace(hook, insert));
    } else {
      this.log('Route exist, skipping');
    }
  }
};
