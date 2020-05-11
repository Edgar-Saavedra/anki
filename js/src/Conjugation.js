import React from 'react';

export class Conjugation extends React.Component {
  constructor(props) {
    super(props);
  }

  printConjugations(conjugations) {
    return Object.keys(conjugations).map((person, key) => (
      <div key={"person"+key}>{conjugations[person].pronoun} : {conjugations[person].conjugation}</div>
    ))
  }

  printTense(tense) {
    return tense.conjugations ? this.printConjugations(tense.conjugations): null
  }

  printConjugation(element, conjugation) {
    return typeof element[conjugation] !== "undefined" && typeof element[conjugation].tenses !== "undefined" ? Object.keys(element[conjugation].tenses).map((tense, key) => (
      <div key={element[conjugation].tenses[tense].tense}>
        <h3>{element[conjugation].tenses[tense].tense_category} - {element[conjugation].tenses[tense].tense}</h3>
        {this.printTense(element[conjugation].tenses[tense])}
      </div>
    )) : null
  }
  printLangConjugations(element) {
    if(element.language == 'german')
    return <div>
      { this.printConjugation(this.props.element, 'indicative') }
      { this.printConjugation(this.props.element, 'conjunctive_i_and_ii') }
      { this.printConjugation(this.props.element, 'conditional') }
      { this.printConjugation(this.props.element, 'imperative') }
    </div>
    if(element.language == 'french')
    return <div>
      <h3>Conjugations</h3>
      { this.printConjugation(this.props.element, 'indicatif') }
      { this.printConjugation(this.props.element, 'subjonctif') }
      { this.printConjugation(this.props.element, 'conditionnel') }
      { this.printConjugation(this.props.element, 'imperatif') }
    </div>
  }
  render() {
    let definition = null;
    if(this.props.wordData && this.props.wordData[this.props.elementKey])
      definition = this.props.wordData[this.props.elementKey];
    return <div className="translation">
      <h1>{this.props.element.translation}</h1>
      <h2>{this.props.element.verb}</h2>
      {definition && definition.definition.has_mp3 ? 
          <audio controls><source src={`${definition.definition.info.mp3.value}`} type="audio/mpeg"/></audio>
        : null}
      <div dangerouslySetInnerHTML={{ __html: this.props.element.nominal_forms ? this.props.element.nominal_forms.content : null }}></div>
      {
        this.printLangConjugations(this.props.element)
      }
      {this.props.element.translations? 
        <div>
          <h3>Translations</h3>
          <div dangerouslySetInnerHTML={{ __html: this.props.element.translations ? this.props.element.translations.content : null }}></div>
        </div>
      : null}
      {this.props.element.etymology? 
        <div>
          <h3>Etymology</h3>
          <div dangerouslySetInnerHTML={{ __html: this.props.element.etymology ? this.props.element.etymology.content : null }}></div>
        </div>
      : null}
      {this.props.element.sample_sentences? 
        <div>
          <h3>Sample Sentences</h3>
          <div dangerouslySetInnerHTML={{ __html: this.props.element.sample_sentences ? this.props.element.sample_sentences.content : null }}></div>
        </div>
      : null}
    </div>;
  }
}
