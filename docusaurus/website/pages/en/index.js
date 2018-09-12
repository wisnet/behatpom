/**
 * Copyright (c) 2017-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

const React = require('react');

const CompLibrary = require('../../core/CompLibrary.js');
const MarkdownBlock = CompLibrary.MarkdownBlock; /* Used to read markdown */
const Container = CompLibrary.Container;
const GridBlock = CompLibrary.GridBlock;

const siteConfig = require(process.cwd() + '/siteConfig.js');

function imgUrl(img) {
    return siteConfig.baseUrl + 'img/' + img;
}

function docUrl(doc, language) {
    return siteConfig.baseUrl + 'docs/' + (language ? language + '/' : '') + doc;
}

function pageUrl(page, language) {
    return siteConfig.baseUrl + (language ? language + '/' : '') + page;
}


class Hero extends React.Component {
    render() {
	return <div className="hero">{this.props.children}</div>;
    }
}

class HeaderHero extends React.Component {
    render() {
	return (
		<Hero>
		<div className="text">Quality Assurance: No Errors!</div>
		<div className="minitext">
		Test Desktop and Mobile apps using Chrome Extension, Behat and Page Object Models
            </div>
		</Hero>
	);
    }
}

class Button extends React.Component {
    render() {
	return (
		<div className="pluginWrapper buttonWrapper">
		<a className="button" href={this.props.href} target={this.props.target}>
		{this.props.children}
            </a>
		</div>
	);
    }
}

Button.defaultProps = {
    target: '_self',
};

const SplashContainer = props => (
	<div className="homeContainer">
	<div className="homeSplashFade">
	<div className="wrapper homeWrapper">{props.children}</div>
	</div>
	</div>
);

const Logo = props => (
	<div className="projectLogo">
	<img src={props.img_src} />
	</div>
);

const ProjectTitle = props => (
	<h2 className="projectTitle">
	{siteConfig.title}
	<small>{siteConfig.tagline}</small>
	</h2>
);

const PromoSection = props => (
	<div className="section promoSection">
	<div className="promoRow">
	<div className="pluginRowBlock">{props.children}</div>
	</div>
	</div>
);


const Block = props => (
	<Container
    padding={['bottom', 'top']}
    id={props.id}
    background={props.background}>
	<GridBlock align="center" contents={props.children} layout={props.layout} />
	</Container>
);

const Features = props => (
	<Block layout="fourColumn">
	{[
	    {
		content: 'Behat is an open source Behavior-Driven Development framework for PHP. It is a tool to support you in delivering software that matters through continuous communication, deliberate discovery and test-automation.',
		title: 'Behat',
	    },	    
	    {
		content: 'Use a Chrome Extension to generate Page Object Model and save the page url and parameters',
		title: 'Chrome Extension',
	    },
	    {
		content: 'Page object pattern is a way of keeping your context files clean by separating UI knowledge from the actions and assertions.',
		title: 'Page Object Model',
	    },
	    {
		content: 'Keep your Gherkin scripts free from redunant statements',
		title: 'Snippets',
	    },
	    {
		content: 'Augment Snippets to process parameters and keep the Gherkin scripts lean and mean',
		title: 'Partials',
	    },
	    {
		content: 'See what code is covered by the testing',
		title: 'Code coverage',
	    },
	    {
		content: 'Integration with PSYSH, a runtime developer console, interactive debugger and REPL for PHP.',
		title: 'PSYSH',
	    },
	    {
		content: 'Run your Gherkin scripts in the cloud using multiple devices and produce a report showing all the devices and browsers screen captures',
		title: 'CrossBrowserTesting.com integration',
	    }
	]}
    </Block>
);

const FeatureCallout = props => (
	<div className="productShowcaseSection"
    style={{textAlign: 'center'}}>
	<h2>Feature Callout</h2>
	</div>
);

const LearnHow = props => (
	<Block background="light">
	{[
	    {
		content: 'Talk about learning how to use this',
		image: imgUrl('docusaurus.svg'),
		imageAlign: 'right',
		title: 'Learn How',
	    },
	]}
    </Block>
);

const TryOut = props => (
	<Block id="try">
	{[
	    {
		content: 'Talk about trying this out',
		image: imgUrl('docusaurus.svg'),
		imageAlign: 'left',
		title: 'Try it Out',
	    },
	]}
    </Block>
);

const Description = props => (
	<Block background="dark">
	{[
	    {
		content: 'This is another description of how this project is useful',
		image: imgUrl('docusaurus.svg'),
		imageAlign: 'right',
		title: 'Description',
	    },
	]}
    </Block>
);


class Index extends React.Component {
    render() {
	let language = this.props.language || '';

	return (
		<div>
		<HeaderHero />
		<Features />
		</div>
	);
    }
}

module.exports = Index;
