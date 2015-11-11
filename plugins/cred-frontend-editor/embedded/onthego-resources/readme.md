# OnTheGo Systems Branding

This is the branding resource handler that takes care of loading all admin icons for our plugins.
The icons are handled as font custom resources.

The icons should be designed and saved in .svg format so they can be compiled to fonts using Font Custom compiler.

The project contains also the styles for all the icons sizes, colors plus a series of rules to override special cases.

Reference: https://docs.google.com/document/d/1exGuxKSqlyPEPQo9LsJ802UIKII7Fu4NY2MlSSoVdg8/edit#heading=h.aniqo1ybb3e2

## Usage:

The plugins can include this code as a git sub-tree.

### Loading code and resources:

The loader.php should be included early in the main plugin.php file. Then the onthego_intialize function should be called. The onthego_intialize function should be passed the file path to the directory where loader.php is and the URL to the directory where loader.php is.

		require PATH_TO_ONTHEGO_RESOURCES_DIR . 'loader.php';
		onthego_initialize(PATH_TO_ONTHEGO_RESOURCES_DIR, URL_TO_ONTHEGO_RESOURCES_DIR);



### Loading the latest version of the resources when multiple plugins use the code:

Normally the first plugin that runs its code will determine what resources get loaded. 

loader.php is responsible for loading the latest version of the resources. The loader.php has a version number and keeps a record of the path and url for that version of the code.

Then after all the plugins code are loaded it uses the ‘plugins_loaded’ hook to then find the latest version of the code and then uses the matching path to load further php files and the matching url for loading the resources.

This way a user can have various versions of the onthego branding resources in his plugins but the code will always ensure that the latest version is used.


			// This version number should always be incremented by 1 whenever a change
			// is made to the onthego-resources code.
			// The version number will then be used to work out which plugin has the latest
			// version of the code.
			 
			$onthegosystems_branding_version = 1;
			 
			 
			// ----------------------------------------------------------------------//
			// WARNING * WARNING *WARNING
			// ----------------------------------------------------------------------//
			 
			// Don't modify or add to this code.
			// This is only responsible for making sure the latest version of the resources
			// is loaded.
			 
			global $onthegosystems_branding_paths;
			 
			if (!isset($onthegosystems_branding_paths)) {
			    $onthegosystems_branding_paths = array();
			}
			 
			if (!isset($onthegosystems_branding_paths[$onthegosystems_branding_version])) {
			    // Save the path to this version.
			    $onthegosystems_branding_paths[$onthegosystems_branding_version]['path'] = str_replace('\\', '/', dirname(__FILE__));
			}


			function onthego_initialize($path, $url) {
			        global $onthegosystems_branding_paths;
			 
			        $path = str_replace('\\', '/', $path);
			 
			        if (substr($path, strlen($path) - 1) == '/') {
			            $path = substr($path, 0, strlen($path) - 1);
			        }
			 
			        // Save the url in the matching path
			        foreach ($onthegosystems_branding_paths as $key => $data) {
			            if ($onthegosystems_branding_paths[$key]['path'] == $path) {
			                $onthegosystems_branding_paths[$key]['url'] = $url;
			                break;
			            }
			        }
			    }
			 

			function on_the_go_systems_branding_plugins_loaded()
			    {
			        global $onthegosystems_branding_paths;
			 
			        // find the latest version
			        $latest = 0;
			        foreach ($onthegosystems_branding_paths as $key => $data) {
			            if ($key > $latest) {
			                $latest = $key;
			            }
			        }
			 
			        if ($latest > 0) {
			            require_once $onthegosystems_branding_paths[$latest]['path'] .          '/onthegosystems-branding-loader.php';
			            ont_set_on_the_go_systems_uri_and_start( $onthegosystems_branding_paths[$latest]['url'] );
			        }
			    }
			 
			    add_action( 'plugins_loaded', 'on_the_go_systems_branding_plugins_loaded');
