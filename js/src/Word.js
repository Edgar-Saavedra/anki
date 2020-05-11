import React from 'react';

export class Word extends React.Component {
  constructor(props) {
    super(props);
  }
  getDefinitions(data) {
    const defs = [];
    data.map((value, index) => {
      defs.push(<li key={index} dangerouslySetInnerHTML={{ __html: value }}></li>)
    })
    return defs;
  }
  renderTemplate() {
    if(!this.props.element.not_found) {
      const genderColor = {
        f: 'red',
        m: 'blue',
        n: 'green'
      };
      let color = null;
      if(this.props.element.has_gender == true) {
        color = genderColor[this.props.element.gender];
      }
      return <div className="translation">
        <h2 style={
          {
            color: color ? color : ''
          }
        } dangerouslySetInnerHTML={{ __html: this.props.element.definition.word ? this.props.element.definition.word : null }}></h2>
        <h2>{this.props.element.translation}</h2>
        {this.props.element.definition.has_mp3 ? 
          <audio controls><source src={`${this.props.element.definition.info.mp3.value}`} type="audio/mpeg"/></audio>
        : null}
        {this.props.element.definition.info.grammatical_info? 
          <div>
            <h4>Grammatical Info</h4>
            <ul><li dangerouslySetInnerHTML={{ __html: this.props.element.definition.info.grammatical_info.value ? this.props.element.definition.info.grammatical_info.value : null }}></li></ul>
          </div>
        : null}
        {this.props.element.definition_found? 
          <div>
            <h4>Definition</h4>
            <ul>{this.getDefinitions(this.props.element.definition.value)}</ul>
          </div>
        : null}
      </div>
    }
    console.log('Word not found', this.props.element);
    return <div></div>
  }
  render() {
    return this.renderTemplate();
  }
}
