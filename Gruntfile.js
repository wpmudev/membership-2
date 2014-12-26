
module.exports = function( grunt ) {
	var paths = {
		js_files_concat: {
			'app/assets/js/ms-admin.js': [
				'app/assets/js/src/ms-admin.js',
				'app/assets/js/src/ms-functions.js',
				'app/assets/js/src/ms-tooltip.js',
				'app/assets/js/src/ms-view-help.js',
				'app/assets/js/src/ms-view-member-date.js',
				'app/assets/js/src/ms-view-member-list.js',
				'app/assets/js/src/ms-view-membership-choose-type.js',
				'app/assets/js/src/ms-view-membership-overview.js',
				'app/assets/js/src/ms-view-membership-setup-payment.js',
				'app/assets/js/src/ms-view-settings.js',
				'app/assets/js/src/ms-view-addons.js',
				'app/assets/js/src/ms-view-settings-automated-msg.js',
				'app/assets/js/src/ms-view-settings-mailchimp.js',
				'app/assets/js/src/ms-view-settings-payment.js',
				'app/assets/js/src/ms-view-settings-protection.js'
			],
			'app/assets/js/ms-public-ajax.js': ['app/assets/js/src/ms-public-ajax.js'],
			'app/assets/js/jquery.plugins.js': [
				'app/assets/js/vendor/jquery.nearest.js'
			],
			'app/assets/js/select2.js': ['app/assets/js/vendor/select2.js']
		},

		css_files_compile: {
			'app/assets/css/ms-admin.css':          'app/assets/css/sass/ms-admin.scss',
			'app/assets/css/ms-public.css':         'app/assets/css/sass/ms-public.scss',
			'app/assets/css/select2.css':           'app/assets/css/sass/select2/select2.scss',
			'app/assets/css/font-awesome.css':      'app/assets/css/sass/font-awesome/font-awesome.scss',
			'app/assets/css/jquery-ui.custom.css':  'app/assets/css/sass/jquery-ui/jquery-ui-1.10.4.custom.scss'
		},

		plugin_dir: 'protected-content/'
	};

	// Project configuration
	grunt.initConfig( {
		pkg:    grunt.file.readJSON( 'package.json' ),

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
				files: paths.js_files_concat
			}
		},


		jshint: {
			all: [
				'Gruntfile.js',
				'app/assets/js/src/**/*.js',
				'app/assets/js/test/**/*.js'
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


		uglify: {
			all: {
				files: [{
					expand: true,
					src: ['*.js', '!*.min.js'],
					cwd: 'app/assets/js/',
					dest: 'app/assets/js/',
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


		test:   {
			files: ['app/assets/js/test/**/*.js']
		},


		sass:   {
			all: {
				options: {
					'sourcemap=none': true, // 'sourcemap': 'none' does not work...
					unixNewlines: true,
					style: 'expanded'
				},
				files: paths.css_files_compile
			}
		},


		autoprefixer: {
			options: {
				browsers: ['last 2 version', 'ie 8', 'ie 9'],
				diff: false
			},
			single_file: {
				files: [{
					expand: true,
					src: ['*.css', '!*.min.css'],
					cwd: 'app/assets/css/',
					dest: 'app/assets/css/',
					ext: '.css',
					extDot: 'last'
				}]
			}
		},


		//compass - required for autoprefixer
		compass: {
			options: {
			},
			server: {
				options: {
					debugInfo: true
				}
			}
		},


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
				cwd: 'app/assets/css/',
				dest: 'app/assets/css/',
				ext: '.min.css',
				extDot: 'last'
			}
		},


		watch:  {
			sass: {
				files: [
					'app/assets/css/sass/**/*.scss'
				],
				tasks: ['sass', 'autoprefixer'/*, 'cssmin'*/],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: [
					'app/assets/js/src/**/*.js',
					'app/assets/js/vendor/**/*.js'
				],
				tasks: ['jshint', 'concat'/*, 'uglify'*/],
				options: {
					debounceDelay: 500
				}
			}
		},


		clean: {
			main: {
				src: ['release/<%= pkg.version %>']
			},
			temp: {
				src: [
					'**/*.tmp',
					'**/.afpDeleted*',
					'**/.DS_Store'
				],
				dot: true,
				filter: 'isFile'
			}
		},


		copy: {
			// Copy the plugin to a versioned release directory
			main: {
				src:  [
					'**',
					'!.git/**',
					'!.git*',
					'!node_modules/**',
					'!release/**',
					'!.sass-cache/**',
					'!**/package.json',
					'!**/css/sass/**',
					'!**/js/src/**',
					'!**/js/vendor/**',
					'!**/img/src/**',
					'!**/Gruntfile.js'
				],
				dest: 'release/<%= pkg.version %>/'
			}
		},


		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>/',
				src: [ '**/*' ],
				dest: paths.plugin_dir
			}
		}

	} );

	// Load other tasks
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-autoprefixer');

	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compress');

	grunt.registerTask( 'release_notes', 'Show release notes', function() {
		grunt.log.subhead( 'Release notes' );
		grunt.log.writeln( '  1. Check BITBUCKET for pull-requests' );
		grunt.log.writeln( '  2. Check ASANA for high-priority bugs' );
		grunt.log.writeln( '  3. Check EMAILS for high-priority bugs' );
		grunt.log.writeln( '  4. Check FORUM for open threads' );
		grunt.log.writeln( '  5. REPLY to forum threads + unsubscribe' );
		grunt.log.writeln( '  6. Update the TRANSLATION files' );
		grunt.log.writeln( '  7. Generate ARCHIVE' );
		grunt.log.writeln( '  8. INSTALL on a clean WordPress installation' );
		grunt.log.writeln( '  9. RELEASE the plugin!' );
	});

	// Default task.

	grunt.registerTask( 'default', ['clean:temp', 'jshint', 'concat', 'uglify', 'sass', 'autoprefixer', 'cssmin', 'release_notes'] );
	grunt.registerTask( 'build', ['default', 'clean', 'copy', 'compress', 'release_notes'] );

	grunt.util.linefeed = '\n';
};