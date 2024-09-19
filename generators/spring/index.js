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
  fields = [];
  package = '';
  relations = [];
  enums = [];
  rawRelations = [];
  rawEnums = [];
  rawJsons = [];
  jsons = [];
  mandatory = [];
  mandatoryEnums = [];
  allMandatory = [];
  capEntityName;
  snakeEntityName; //dashed(-) snake entity name
  tableName;
  camelEntityName;
  camelPluralEntityName;
  capPluralEntityName;
  entityName;
  entityNamePlural;
  resourcePath;
  destination;
  parentFolder;

  constructor(args, opts) {
    super(args, opts);
    this.registerTransformStream(prettierTransform({}, this, true));
  }

  async prompting() {
    await this._getEntityName();
     await this._getParentFolderName();
    await this._getFields();
    if (this.rawRelations.length) {
      await this._getMandator();
    }
    if (this.rawEnums.length) {
      await this._getMandatoEnums();
    }
  }

  async _getEntityName() {
    //Read entities from directory .entities inside project root directory
    const entityOptions = [];
    fs.readdirSync('.entities').forEach(file => {
      entityOptions.push({
        name: file.replace('.json', ''),
        value: file,
      });
    });

    //Prompt selection of entity
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
            name: 'modules',
            value: 'modules',
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


  /**
   * Reading fields from entities file
   */
  async _getFields() {
    let data = await this.fs.readJSON(this.destinationPath('.entities/' + this.entityNameInput.name));
    if (data == undefined) {
      this.log('invalid path ');
      return;
    }
    if (data.fields === undefined) {
      this.log('json file mut has fields array prop');
      return;
    } else {
      this.package = data.package;
      //remove created, updated fields and timestamp fields
      this.fields = data.fields.filter(f => {
        return !f.name.includes('created') && !f.name.includes('updated');
      });
    }
    //Read foreign or manay to many relationship fields
    this.rawRelations = this.fields.filter(f => f.relation != undefined);
    //Read enumns fields
    this.rawEnums = this.fields.filter(f => f.dbType === 'enum');
    //Read json type fields
    this.rawJsons = this.fields.filter(f => f.dbType === 'json');
  }

  /**
   * Get fields that should be mandatory filter before loading data to table
   */
  async _getMandator() {
    //Get mandatory choice fields from foreign/relatioship fields
    const choices = this.rawRelations.map(r => {
      return {
        name: r.name.replace('_id', ''),
        value: r.name,
      };
    });
    //Prompt option selection
    const mandatory = await this.prompt([
      {
        type: 'checkbox',
        name: 'filters',
        message: 'Select Mandatory Filters',
        choices,
      },
    ]);
    this.mandatory = mandatory.filters || [];
  }

  /**
   * Get mandatory filters from enums fields
   */
  async _getMandatoEnums() {
    const choices = this.rawEnums.map(r => {
      return {
        name: r.name,
        value: r.name,
      };
    });
    const mandatoryEnums = await this.prompt([
      {
        type: 'checkbox',
        name: 'selection',
        message: 'Select Mandatory Enums Filters',
        choices,
      },
    ]);
    this.mandatoryEnums = mandatoryEnums.selection || [];
  }

  /**
   * Writing fields
   */
  writing() {
    this._init();
    this._generateEntity();
    this._generateDto();
    this._generateRepo();
    this._generateMapper();
    this._generateService();
    this._generateServiceImpl();
    this._generateController();
    this._generateMigration();
  }

  _init() {
    //component parent folder directory
     this.parentFolder = this.parentFolderInput.name.toLowerCase();

    //
    const fieldName = pluralize.singular(this.entityNameInput.name.replace('.json', ''));

    //Split entity name words separated by underscorre
    const words = fieldName.split('_');

    //Captial entity name
    this.capEntityName = '';

    //For each word of the entity change to capitalized and join to entity name
    //ie. create capitlaized name of the entity
    words.forEach(w => {
      // ws = pluralize.singular(w).toLowerCase();
      this.capEntityName = this.capEntityName + w.charAt(0).toUpperCase() + w.slice(1);
    });

    //Create singular camel case name of the ntity
    this.camelEntityName = this.capEntityName.charAt(0).toLowerCase() + this.capEntityName.slice(1);

    //create plural camel case of entity name
    this.camelPluralEntityName = pluralize(this.camelEntityName);

    this.capPluralEntityName = pluralize(this.capEntityName);

    //Singular Word spaced entity name, Human readable used in dialog headers etc
    this.entityName = _.startCase(fieldName.replace('_', ' '));

    //Plural Word spaced entity name, Human readable
    this.entityNamePlural = pluralize(this.entityName);

    // - snaked case for imports and selectors
    this.snakeEntityName = fieldName;

    //api resource path
    this.tableName = pluralize(fieldName);

    this.resourcePath = pluralize(fieldName.replaceAll('_', '-'));

    // add header property used by labels
    this.fields = this.fields.map(f => {
      let fCap = '';
      let fWords = f.name.split('_');
      fWords.forEach(w => (fCap = fCap + w.charAt(0).toUpperCase() + w.slice(1)));
      //camel filed name
      f.camelName = fCap.charAt(0).toLowerCase() + fCap.slice(1);

      return {
        ...f,
        header: _.startCase(f.name).replace('Id', ''),
      };
    });

    //prepare relation fields
    this.relations = this.rawRelations.map(r => {
      //Get properires foth the relationship definition
      const props = r.relation.split(',');

      //Relation ship type
      const type = props[0];

      //relationship singular capitalized name
      let rlCap = '';

      //removing trailing _id from relationship field name
      let rlName = r.name.replace('_id', '');

      // Get words from rlationship filed name separeanted by _
      let rlWords = rlName.split('_');

      //foreach word change first letter to uppercase and join to rlCap variable
      rlWords.forEach(w => (rlCap = rlCap + w.charAt(0).toUpperCase() + w.slice(1)));

      //relationship camel case name used as relation variable names
      const rlCamel = rlCap.charAt(0).toLowerCase() + rlCap.slice(1);

      //relation _ snake case name used for import relation entity
      const rlSnake = rlCap.replace(/[A-Z]/g, (letter, index) => {
        return index == 0 ? letter.toLowerCase() : '_' + letter.toLowerCase();
      });

      //Relation camel case plural used for relation collection variable name
      const rlCamelPlural = pluralize(rlCamel);

      const rlCapPlural = pluralize(rlCap);

      const rlTableName = pluralize(rlSnake);

      //If this relation is mandatory filter
      const mandatory = this.mandatory.indexOf(r.name) !== 1;

      //return relation variables
      return {
        ...r,
        rlCap,
        rlCapPlural,
        type,
        rlCamel,
        rlSnake,
        rlCamelPlural,
        mandatory,
        rlTableName,
        header: _.startCase(r.name).replace('Id', ''),
      };
    });

    //prepare enums relatioships
    this.enums = this.rawEnums.map(e => {
      // field type name
      const type = 'enum';

      //enum caitilized name
      let eCap = '';
      //remove _id from relations name
      let eName = e.name.replace('_id', '');

      //get enum field names separeted by _
      let eWords = eName.split('_');

      //Foreach word capitalize then join to create eCap variable
      eWords.forEach(w => (eCap = eCap + w.charAt(0).toUpperCase() + w.slice(1)));

      //Create camel case for enum variable name
      const eCamel = eCap.charAt(0).toLowerCase() + eCap.slice(1);

      //create - snake case name for this enumn
      const eSnake = eCap.replace(/[A-Z]/g, (letter, index) => {
        return index == 0 ? letter.toLowerCase() : '-' + letter.toLowerCase();
      });

      //Enumum plural came case for enum collectio variable name
      const eCamelPlural = pluralize(eCamel);

      //If this enum is mandatiry filter
      const mandatory = this.mandatoryEnums.indexOf(e.name) !== 1;

      return {
        ...e,
        eCap,
        type,
        eCamel,
        eSnake,
        eCamelPlural,
        mandatory,
        header: _.startCase(e.name).replace('Id', ''),
      };
    });

    //For each json type fields collection
    this.jsons = this.rawJsons.map(j => {
      const type = 'json';

      //catialized name for json field name
      let jCap = '';

      //remove _id and _ids string from json field
      let jName = j.name.replace('_ids', '').replace('_id', '');

      //get json field names separeted by _
      let jWords = jName.split('_');

      //for each word captilze and join to form jCap
      jWords.forEach(w => (jCap = jCap + w.charAt(0).toUpperCase() + w.slice(1)));

      //create json dield came case variable name
      const jCamel = jCap.charAt(0).toLowerCase() + jCap.slice(1);

      // create json field - snake case for imports
      const jSnake = jCap.replace(/[A-Z]/g, (letter, index) => {
        return index == 0 ? letter.toLowerCase() : '-' + letter.toLowerCase();
      });

      //create json field plural camel case for json collection variable name
      const jCamelPlural = pluralize(jCamel);

      // if this json columns is manadatory fields
      const mandatory = this.mandatoryEnums.indexOf(j.name) !== 1;

      return {
        ...j,
        jCap,
        type,
        jCamel,
        jSnake,
        jCamelPlural,
        mandatory,
        header: _.startCase(j.name).replace('Id', ''),
      };
    });

    //
    this.allMandatory = this.mandatory.concat(this.mandatoryEnums);
    this.destination = `src/main/java/${this.package.split('.').join('/')}/${this.parentFolder}/${this.snakeEntityName}`;
  }

  /**
   * Generate entity file
   */
  _generateEntity() {
    this.fs.copyTpl(this.templatePath('entity.java.ejs'), this.destinationPath(`${this.destination}/${this.capEntityName}.java`), {
      camelEntityName: this.camelEntityName,
      camelPluralEntityName: this.camelPluralEntityName,
      capEntityName: this.capEntityName,
      snakeEntityName: this.snakeEntityName,
      fields: this.fields,
      package: this.package,
      relations: this.relations,
      parentFolder: this.parentFolder,
      tableName: this.tableName,
    });
  }

  _generateDto() {
    this.fs.copyTpl(this.templatePath('dto.java.ejs'), this.destinationPath(`${this.destination}/${this.capEntityName}Dto.java`), {
      camelEntityName: this.camelEntityName,
      capEntityName: this.capEntityName,
      snakeEntityName: this.snakeEntityName,
      fields: this.fields,
      parentFolder: this.parentFolder,
      relations: this.relations,
      package: this.package,
    });
  }

  _generateRepo() {
    this.fs.copyTpl(
      this.templatePath('repository.java.ejs'),
      this.destinationPath(`${this.destination}/${this.capEntityName}Repository.java`),
      {
        camelEntityName: this.camelEntityName,
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        parentFolder: this.parentFolder,
        package: this.package,
      }
    );
  }

  _generateService() {
    this.fs.copyTpl(this.templatePath('service.java.ejs'), this.destinationPath(`${this.destination}/${this.capEntityName}Service.java`), {
      camelEntityName: this.camelEntityName,
      capEntityName: this.capEntityName,
      snakeEntityName: this.snakeEntityName,
      fields: this.fields,
      parentFolder: this.parentFolder,
      package: this.package,
    });
  }

  _generateServiceImpl() {
    this.fs.copyTpl(
      this.templatePath('service-impl.java.ejs'),
      this.destinationPath(`${this.destination}/${this.capEntityName}ServiceImpl.java`),
      {
        camelEntityName: this.camelEntityName,
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        parentFolder: this.parentFolder,
        package: this.package,
      }
    );
  }

  _generateMapper() {
    this.fs.copyTpl(this.templatePath('mapper.java.ejs'), this.destinationPath(`${this.destination}/${this.capEntityName}Mapper.java`), {
      camelEntityName: this.camelEntityName,
      capEntityName: this.capEntityName,
      snakeEntityName: this.snakeEntityName,
      fields: this.fields,
      parentFolder: this.parentFolder,
      package: this.package,
    });
  }

  _generateController() {
    this.fs.copyTpl(
      this.templatePath('controller.java.ejs'),
      this.destinationPath(`${this.destination}/${this.capEntityName}Resource.java`),
      {
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        package: this.package,
        parentFolder: this.parentFolder,
        resourcePath: this.resourcePath,
        relations: this.relations,
        allMandatory: this.allMandatory,
      }
    );
  }

  _generateMigration() {
    const now = new Date();
    const timestamp = now.toISOString().replace(/[-:TZ.]/g, '');

    this.fs.copyTpl(
      this.templatePath('migration.sql.ejs'),
      this.destinationPath(`src/main/resources/db/migration/${timestamp}__add_table_${this.tableName}.sql`),
      {
        camelEntityName: this.camelEntityName,
        camelPluralEntityName: this.camelPluralEntityName,
        capEntityName: this.capEntityName,
        snakeEntityName: this.snakeEntityName,
        fields: this.fields,
        relations: this.relations,
        tableName: this.tableName,
      }
    );
  }
};
