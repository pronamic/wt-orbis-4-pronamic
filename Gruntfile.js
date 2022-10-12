module.exports = function( grunt ) {
	// Project configuration.
	require( 'load-grunt-tasks' )( grunt );

	var phpFiles = [
		'**/*.php',
		'!node_modules/**',
		'!bower_components/**',
		'!deploy/**',
		'!vendor/**'
	];

	grunt.initConfig( {
		// Package
		pkg: grunt.file.readJSON( 'package.json' ),
		
		// PHPLint
		phplint: {
			options: {
				phpArgs: {
					'-lf': null
				}
			},
			all: [ '**/*.php', '!node_modules/**', '!bower_components/**' ]
		},
		
		// MakePOT
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: 'languages',
					type: 'wp-theme',
					exclude: [ 'bower_components/.*', 'node_modules/.*' ],
				}
			}
		},

		// PHP Code Sniffer
		phpcs: {
			application: {
				src: phpFiles
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'phpcs.ruleset.xml',
				showSniffCodes: true
			}
		},

		// Check textdomain errors
		checktextdomain: {
			options:{
				text_domain: 'orbis_pronamic',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php',
					'!deploy/**',
					'!node_modules/**',
					'!tests/**',
					'!wp-content/**'
				],
				expand: true
			}
		},
	} );

	grunt.loadNpmTasks( 'grunt-phplint' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );

	// Default task(s).
	grunt.registerTask( 'default', [ 'phplint' ] );
	grunt.registerTask( 'pot', [ 'checktextdomain', 'makepot' ] );
};
