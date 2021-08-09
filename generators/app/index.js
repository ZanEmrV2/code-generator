var Generator = require('yeoman-generator');
var beautify = require("gulp-beautify");

module.exports = class extends Generator {
    entity;
    columns =[];
    constructor(args, opts) {
        super(args, opts);
        // this.registerTransformStream(beautify({ indent_size: 2 }));
    }

    async prompting() {
      await this._entity()
      await this._field()
    }

    async _entity() {
        this.entity = await this.prompt([
            {
              type: "input",
              name: "name",
              message: "What is the name of the entity",
            },
        ])
    }

    async _field() {
        this.answers = await this.prompt([
            {
              type: "input",
              name: "name",
              message: "What is the name of the field",
            },
            {
              type: "list",
              name: "type",
              message: "What is the name of the field",
              choices: [
                  {
                      name: 'Text',
                      value: 'Text'
                  },
                  {
                      name: 'Select',
                      value: 'Select'
                  }
              ]
            },
            {
              type: "confirm",
              name: "next",
              message: "Would you like to add another field?"
            }
          ]);
          this.columns.push(this.answers)
          if(this.answers.next) {
             await this._field()
          }
    }
    
      writing() {
        this.fs.copyTpl(
            this.templatePath('index.html.ejs'),
            this.destinationPath(`public/${this.entity.name.toLowerCase()}.component.html`),
            { title: this.entity.name, columns:this.columns }
        );

        this.fs.copyTpl(
            this.templatePath('index.ts'),
            this.destinationPath(`public/${this.entity.name.toLowerCase()}.component.ts`),
            { title: this.entity.name }
        );
          //this.log(this.entity)
         // this.log(this.columns)
      }

};

