import React from 'react';
import {
  Conjugation
} from "./Conjugation";

export class App extends React.Component {
  constructor(props) {
    super(props);
  }
  loadTabels() {
    return this.props.germanData.map((element, key) => (
        <Conjugation element={element} key={key}></Conjugation>
    ));
  }
  render() {
    return <div>{this.loadTabels()}</div>;
  }
}
