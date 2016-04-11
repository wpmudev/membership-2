/*global require*/

/**
 * When grunt command does not execute try these steps:
 *
 * - delete folder 'node_modules' and run command in console:
 *   $ npm install
 *
 * - Run test-command in console, to find syntax errors in script:
 *   $ grunt hello
 */

module.exports = function( grunt ) {
	// Show elapsed time at the end.
	require( 'time-grunt' )(grunt);

	// Load all grunt tasks.
	require( 'load-grunt-tasks' )(grunt);

	var buildtime = new Date().toISOString();

	var conf = {
		// Folder that contains the CSS files.
		js_folder: 'app/assets/js/',

		// Folder that contains the CSS files.
		css_folder: 'app/assets/css/',

		// Concatenate those JS files into a single file (target: [source, source, ...]).
		js_files_concat: {
			'{js}/ms-admin.js': [
				'{js}/src/ms-loader.js',
				'{js}/src/ms-functions.js',
				'{js}/src/ms-inline-editor.js',
				'{js}/src/ms-tooltip.js',
				'{js}/src/ms-controller-adminbar.js',
				'{js}/src/ms-view-help.js',
				'{js}/src/ms-view-billing-edit.js',
				'{js}/src/ms-view-billing-transactions.js',
				'{js}/src/ms-view-member-date.js',
				'{js}/src/ms-view-member-editor.js',
				'{js}/src/ms-view-member-list.js',
				'{js}/src/ms-view-membership-add.js',
				'{js}/src/ms-view-membership-list.js',
				'{js}/src/ms-view-membership-metabox.js',
				'{js}/src/ms-view-membership-overview.js',
				'{js}/src/ms-view-membership-render-url-group.js',
				'{js}/src/ms-view-membership-payment.js',
				'{js}/src/ms-view-membership-protected-content.js',
				'{js}/src/ms-view-membership-upgrade.js',
				'{js}/src/ms-view-addons.js',
				'{js}/src/ms-view-settings.js',
				'{js}/src/ms-view-settings-automated-msg.js',
				'{js}/src/ms-view-settings-import.js',
				'{js}/src/ms-view-settings-mailchimp.js',
				'{js}/src/ms-view-settings-payment.js',
				'{js}/src/ms-view-settings-protection.js',
				'{js}/src/ms-view-settings-setup.js'
			],
			'{js}/ms-public.js': [
				'{js}/src/ms-loader.js',
				'{js}/src/ms-shortcode.js',
				'{js}/src/ms-view-frontend-profile.js',
				'{js}/src/ms-view-gateway-authorize.js'
			],
			'{js}/jquery.m2.plugins.js': [ '{js}/vendor/jquery.nearest.js' ],
			'{js}/jquery.m2.validate.js': [ '{js}/vendor/jquery.validate.js' ],
			'{js}/ms-public-ajax.js': ['{js}/src/ms-public-ajax.js']
		},

		// SASS files to process. Resulting CSS files will be minified as well.
		css_files_compile: {
			'{css}/ms-admin.css':          '{css}/sass/ms-admin.scss',
			'{css}/ms-public.css':         '{css}/sass/ms-public.scss'
		},

		// BUILD branches.
		plugin_branches: {
			exclude_pro: [
				'./test',
				'./readme.txt',
			],
			exclude_free: [
				'./test',
				'./premium',
				'./lib/wpmudev-dashboard',
			],
			include_files: [
				'**',
				'!**/css/sass/**',
				'!**/js/src/**',
				'!**/js/vendor/**',
				'!**/img/src/**',
				'!node_modules/**',
				'!.sass-cache/**',
				'!release/**',
				'!Gruntfile.js',
				'!package.json',
				'!build/**',
				'!tests/**',
				'!.git/**',
				'!.git',
				'!.log'
			],
			base: 'free-pro-integration',
			pro: 'm2-pro',
			free: 'm2-free',
		},

		// BUILD patterns to exclude code for specific builds.
		plugin_patterns: {
			pro: [
				{ match: /BUILDTIME/g, replace: buildtime },
				{ match: /\/\* start:pro \*\//g, replace: '' },
				{ match: /\/\* end:pro \*\//g, replace: '' },
				{ match: /\/\* start:free \*[^\*]+\* end:free \*\//mg, replace: '' },
			],
			free: [
				{ match: /BUILDTIME/g, replace: buildtime },
				{ match: /\/\* start:free \*\//g, replace: '' },
				{ match: /\/\* end:free \*\//g, replace: '' },
				{ match: /\/\* start:pro \*[^\*]+\* end:pro \*\//mg, replace: '' },
			],
			// Files to apply above patterns to (not only php files).
			files: {
				expand: true,
				src: [
					'**/*.php',
					'**/*.css',
					'**/*.js',
					'**/*.html',
					'**/*.txt',
					'!node_modules/**',
					'!lib/**',
					'!docs/**',
					'!release/**',
					'!Gruntfile.js',
					'!build/**',
					'!tests/**',
					'!.git/**'
				],
				dest: './'
			}
		},

		// Regex patterns to exclude from transation.
		translation: {
			ignore_files: [
				'node_modules/.*',
				'(^.php)',      // Ignore non-php files.
				'lib/.*',       // External libraries.
				'release/.*',   // Temp release files.
				'tests/.*',     // Unit testing.
				'docs/.*',      // API Documentation.
			],
			pot_dir: 'languages/', // With trailing slash.
			textdomain: 'membership2',
		},

		plugin_dir: 'membership/',
		plugin_file: 'membership2.php'
	};
	// -------------------------------------------------------------------------
	var key, ind, newkey, newval;
	for ( key in conf.js_files_concat ) {
		newkey = key.replace( '{js}', conf.js_folder );
		newval = conf.js_files_concat[key];
		delete conf.js_files_concat[key];
		for ( ind in newval ) { newval[ind] = newval[ind].replace( '{js}', conf.js_folder ); }
		conf.js_files_concat[newkey] = newval;
	}
	for ( key in conf.css_files_compile ) {
		newkey = key.replace( '{css}', conf.css_folder );
		newval = conf.css_files_compile[key].replace( '{css}', conf.css_folder );
		delete conf.css_files_compile[key];
		conf.css_files_compile[newkey] = newval;
	}
	// -------------------------------------------------------------------------

	// Project configuration
	grunt.initConfig( {
		pkg:    grunt.file.readJSON( 'package.json' ),

		// JS - Concat .js source files into a single .js file.
		concat: {
			options: {
				stripBanners: true,
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			scripts: {
				files: conf.js_files_concat
			}
		},

		// JS - Validate .js source code.
		jshint: {
			all: [
				'Gruntfile.js',
				conf.js_folder + 'src/**/*.js',
				conf.js_folder + 'test/**/*.js'
			],
			options: {
				curly:   true,
				eqeqeq:  true,
				immed:   true,
				latedef: true,
				newcap:  true,
				noarg:   true,
				sub:     true,
				undef:   true,
				boss:    true,
				eqnull:  true,
				globals: {
					exports: true,
					module:  false
				}
			}
		},

		// JS - Uglyfies the source code of .js files (to make files smaller).
		uglify: {
			all: {
				files: [{
					expand: true,
					src: ['*.js', '!*.min.js'],
					cwd: conf.js_folder,
					dest: conf.js_folder,
					ext: '.min.js',
					extDot: 'last'
				}],
				options: {
					banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
						' * <%= pkg.homepage %>\n' +
						' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
						' * Licensed GPLv2+' +
						' */\n',
					mangle: {
						except: ['jQuery']
					}
				}
			}
		},

		// TEST - Run the PHPUnit tests.
		phpunit: {
			classes: {
				dir: ''
			},
			options: {
				bin: 'phpunit',
				bootstrap: 'tests/php/bootstrap.php',
				testsuite: 'default',
				configuration: 'tests/php/phpunit.xml',
				colors: true,
				//tap: true,
				//testdox: true,
				//stopOnError: true,
				staticBackup: false,
				noGlobalsBackup: false
			}
		},

		// CSS - Compile a .scss file into a normal .css file.
		sass:   {
			all: {
				options: {
					'sourcemap=none': true, // 'sourcemap': 'none' does not work...
					unixNewlines: true,
					style: 'expanded'
				},
				files: conf.css_files_compile
			}
		},

		// CSS - Automaticaly create prefixed attributes in css file if needed.
		//       e.g. add `-webkit-border-radius` if `border-radius` is used.
		autoprefixer: {
			options: {
				browsers: ['last 2 version', 'ie 8', 'ie 9'],
				diff: false
			},
			single_file: {
				files: [{
					expand: true,
					src: ['*.css', '!*.min.css'],
					cwd: conf.css_folder,
					dest: conf.css_folder,
					ext: '.css',
					extDot: 'last'
				}]
			}
		},


		// CSS - Required for CSS-autoprefixer and maybe some SCSS function.
		compass: {
			options: {
			},
			server: {
				options: {
					debugInfo: true
				}
			}
		},

		// CSS - Minify all .css files.
		cssmin: {
			options: {
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			minify: {
				expand: true,
				src: ['*.css', '!*.min.css'],
				cwd: conf.css_folder,
				dest: conf.css_folder,
				ext: '.min.css',
				extDot: 'last'
			}
		},

		// WATCH - Watch filesystem for changes during development.
		watch:  {
			sass: {
				files: [
					conf.css_folder + 'sass/**/*.scss'
				],
				tasks: ['sass', 'autoprefixer'],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: [
					conf.js_folder + 'src/**/*.js',
					conf.js_folder + 'vendor/**/*.js'
				],
				tasks: ['jshint', 'concat'],
				options: {
					debounceDelay: 500
				}
			}
		},

		// BUILD - Remove previous build version and temp files.
		clean: {
			temp: {
				src: [
					'**/*.tmp',
					'**/.afpDeleted*',
					'**/.DS_Store'
				],
				dot: true,
				filter: 'isFile'
			},
			release_pro: {
				src: [
					'release/<%= pkg.version %>-pro/',
					'release/<%= pkg.version %>-pro-<%= pkg.version %>.zip'
				]
			},
			release_free: {
				src: [
					'release/<%= pkg.version %>-free/',
					'release/<%= pkg.version %>-free-<%= pkg.version %>.zip'
				]
			},
			pro: conf.plugin_branches.exclude_pro,
			free: conf.plugin_branches.exclude_free
		},

		// BUILD - Copy all plugin files to the release subdirectory.
		copy: {
			pro: {
				src: conf.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-pro/'
			},
			free: {
				src: conf.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-free/'
			},
		},

		// BUILD - Create a zip-version of the plugin.
		compress: {
			pro: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-pro-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-pro/',
				src: [ '**/*' ],
				dest: conf.plugin_dir
			},
			free: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-free-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-free/',
				src: [ '**/*' ],
				dest: conf.plugin_dir
			},
		},

		// BUILD - update the translation index .po file.
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: conf.translation.pot_dir,
					exclude: conf.translation.ignore_files,
					mainFile: conf.plugin_file,
					potFilename: conf.translation.textdomain + '.pot',
					potHeaders: {
						poedit: true, // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin' // wp-plugin or wp-theme
				}
			}
		},

		// BUILD: Replace conditional tags in code.
		replace: {
			pro: {
				options: {
					patterns: conf.plugin_patterns.pro
				},
				files: [conf.plugin_patterns.files]
			},
			free: {
				options: {
					patterns: conf.plugin_patterns.free
				},
				files: [conf.plugin_patterns.files]
			}
		},

		// BUILD: Git control (check out branch).
		gitcheckout: {
			pro: {
				options: {
					verbose: true,
					branch: conf.plugin_branches.pro,
					overwrite: true
				}
			},
			free: {
				options: {
					branch: conf.plugin_branches.free,
					overwrite: true
				}
			},
			base: {
				options: {
					branch: conf.plugin_branches.base
				}
			}
		},

		// BUILD: Git control (add files).
		gitadd: {
			pro: {
				options: {
				verbose: true, all: true }
			},
			free: {
				options: { all: true }
			},
		},

		// BUILD: Git control (commit changes).
		gitcommit: {
			pro: {
				verbose: true,
				options: {
					message: 'Built from: ' + conf.plugin_branches.base,
					allowEmpty: true
				},
				files: { src: ['.'] }
			},
			free: {
				options: {
					message: 'Built from: ' + conf.plugin_branches.base,
					allowEmpty: true
				},
				files: { src: ['.'] }
			},
		},

		// DOCS - Execute custom command to build the phpdocs for API
		exec: {
			phpdoc: {
				command: 'phpdoc -f ./app/controller/class-ms-controller-api.php -f ./app/model/class-ms-model-membership.php -f ./app/model/class-ms-model-relationship.php -f ./app/model/class-ms-model-member.php -t ./docs'
			}
		}

	} );

	// Test task.
	grunt.registerTask( 'hello', 'Test if grunt is working', function() {
		grunt.log.subhead( 'Hi there :)' );
		grunt.log.writeln( 'Looks like grunt is installed!' );
	});

	// Plugin build tasks
	grunt.registerTask( 'build', 'Run all tasks.', function(target) {
		var build = [], i, branch;

		if ( target ) {
			build.push( target );
		} else {
			build = ['pro', 'free'];
		}

		// First run unit tests.
		grunt.task.run( 'phpunit' );

		// Run the default tasks (js/css/php validation).
		grunt.task.run( 'default' );

		// Generate all translation files (same for pro and free).
		grunt.task.run( 'makepot' );

		// Update the integrated API documentation.
		grunt.task.run( 'docs' );

		for ( i in build ) {
			branch = build[i];
			grunt.log.subhead( 'Update product branch [' + branch + ']...' );

			// Checkout the destination branch.
			grunt.task.run( 'gitcheckout:' + branch );

			// Remove code and files that does not belong to this version.
			grunt.task.run( 'replace:' + branch );
			grunt.task.run( 'clean:' + branch );

			// Add the processes/cleaned files to the target branch.
			grunt.task.run( 'gitadd:' + branch );
			grunt.task.run( 'gitcommit:' + branch );

			// Create a distributable zip-file of the plugin branch.
			grunt.task.run( 'clean:release_' + branch );
			grunt.task.run( 'copy:' + branch );
			grunt.task.run( 'compress:' + branch );

			grunt.task.run( 'gitcheckout:base');
		}
	});

	// Development tasks.
	grunt.registerTask( 'default', ['clean:temp', 'jshint', 'concat', 'uglify', 'sass', 'autoprefixer', 'cssmin'] );
	grunt.registerTask( 'test', ['phpunit', 'jshint'] );
	grunt.registerTask( 'docs', ['exec:phpdoc'] );

	grunt.task.run( 'clear' );
	grunt.util.linefeed = '\n';
};