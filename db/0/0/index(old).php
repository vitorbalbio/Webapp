<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Vizar WebView</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body {
				font-family: Monospace;
				background-color: #000;
				color: #fff;
				margin: 0px;
				overflow: hidden;
			}

		</style>
	</head>

	<body>
        
        
        <script src="../../lib/three.js"></script>
        
        <script src="../../js/controls/OrbitControls.js"></script>
        <script src="../../js/loaders/RGBELoader.js"></script>
		<script src="../../js/loaders/HDRCubeTextureLoader.js"></script>

		<script src="../../js/loaders/MTLLoader.js"></script>
		<script src="../../js/loaders/OBJLoader.js"></script>
		<script src="../../js/Detector.js"></script>
        
        <script src="../../js/pmrem/PMREMGenerator.js"></script>
        <script src="../../js/pmrem/PMREMCubeUVPacker.js"></script>
        <?php
            echo "<script> var currentsku =".$_GET["sku"]."</script>"
        ?>
        
        
		<script>

			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();
            
            // System
			var container, stats, controls;
			var camera, scene, renderer, light;
			var clock = new THREE.Clock();
            
            // Scene
            var currentObject = null;
            var groundMat;
            
            // currentsku declared in php based on the page id
            
			init();

			function init() {
                
                // SETUP WINDOW -------------------------------------------
				container = document.createElement( 'div' );
				document.body.appendChild( container );
                window.addEventListener( 'resize', onWindowResize, false );
                
                
                // CREATE RENDERER -------------------------------------------
                renderer = new THREE.WebGLRenderer( { antialias: true } );
                renderer.setPixelRatio( window.devicePixelRatio );
				renderer.setSize( window.innerWidth, window.innerHeight );
                container.appendChild( renderer.domElement );
                
                // CREATE CAMERA -------------------------------------------
				camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 0.1, 2000 );
				controls = new THREE.OrbitControls( camera, renderer.domElement );
				
				camera.position.set( 2, 1, -3 );
                controls.target.set( 0, 0.7, 0 );
                controls.enableDamping = true;
                controls.dampingFactor = 0.4;
                controls.minDistance = 2;
                controls.maxDistance = 5;
                controls.enablePan = false;
                controls.minPolarAngle = -Math.PI/2;
                controls.maxPolarAngle = Math.PI/2;
                controls.update();
                
                // CREATE SCENE -------------------------------------------
				scene = new THREE.Scene();
                scene.background = new THREE.Color( 0xFFFFFF );
                
                // Hemi Light
                hemiLight = new THREE.HemisphereLight( 0xffffff, 0xffffff, 0.6 );
				hemiLight.color.setHSL( 0.6, 1, 0.6 );
				hemiLight.groundColor.setHSL( 0.095, 1, 0.75 );
				hemiLight.position.set( 0, 2, 0 );
				scene.add( hemiLight );

                // Directional Light
                dirLight = new THREE.DirectionalLight( 0xffffff, 2 );
				dirLight.position.set( 5, 10, -5 );
				dirLight.position.multiplyScalar( 1 );
				
				dirLight.castShadow = true;
				dirLight.shadow.mapSize.width = 1024;
				dirLight.shadow.mapSize.height = 1024;
				
                var d = 5;
				dirLight.shadow.camera.left = -d;
				dirLight.shadow.camera.right = d;
				dirLight.shadow.camera.top = d;
				dirLight.shadow.camera.bottom = -d;
                
				dirLight.shadow.camera.far = 30;
				dirLight.shadow.bias = -0.001;
                scene.add( dirLight );
                
                
                // DRAW GROUND -------------------------------------------
                //var groundGeo = ;
				groundMat = new THREE.ShadowMaterial();
                groundMat.opacity = 0.0;
				var ground = new THREE.Mesh( new THREE.PlaneBufferGeometry( 5, 5 ), groundMat);
				ground.rotation.x = -Math.PI/2;
                ground.receiveShadow = true;
				scene.add( ground );
				
                
                // FRIST SKU OBJECT LOADER ------------------------------------
                LoadSKUObject()

                // RENDER SETTINGS -------------------------------------------
                renderer.gammaInput = true;
				renderer.gammaOutput = true;
                
                renderer.shadowMap.enabled =  true;
				renderer.shadowMap.renderReverseSided = false;
                renderer.shadowMap.autoUpdate = false;
				animate();

			}

            function LoadSKUObject(){
                // Clear the current object to load another SKU hopping the cache handle to not load it again
                if(currentObject != null){
                    scene.remove(currentObject);
                    currentObject = null;
                }
                
                var mtlLoader = new THREE.MTLLoader();
                mtlLoader.load( 'model.mtl', function( materials ) {
                
					materials.preload();
                    
					var objLoader = new THREE.OBJLoader();
					objLoader.setMaterials( materials );

					objLoader.load( 'model.obj', function ( object ) {
                        for(var i = 0 ; i < object.children.length; i++){
                            if(object.children[i] instanceof THREE.Mesh){

                                name = object.children[i].material.name;
                                var useDefault = true;
                                var mat;
                                
                                object.children[i].castShadow = true;
                                object.children[i].receiveShadow = true;
                                
                                if(name.includes("[Native]")){
                                    continue;
                                }
                                
                                if(name.includes("[Wood]")){
                                    mat = ShaderLoader(2,name);
                                    useDefault = false;
                                }
                                
                                if(name.includes("[Fabric]")){
                                    mat =  ShaderLoader(1,name);
                                    useDefault = false;
                                }
                                
                                if(name.includes("[Metal]")){
                                    mat =  ShaderLoader(3,name);
                                    useDefault = false;
                                }
                                
                                if(name.includes("[Chrome]")){
                                    mat =  ShaderLoader(4,name);
                                    useDefault = false;
                                }
                                
                                if(useDefault){
                                   mat =  ShaderLoader(0,"");
                                }
                                
                                object.children[i].material = mat;
                            }
                        }
                        
                        // Only shows ground when the object is loaded and the shadow calculated
                        groundMat.opacity = 0.3;
                        renderer.shadowMap.needsUpdate = true;
                        
                        scene.add( object );
                        
                        
                        
                        return object;
					});
				});
            }

            function ShaderLoader(Mode,materialName){
                
                var shader;
                var mat = new THREE.MeshStandardMaterial(0x000000);

                // Default
                if(Mode == 0){
                    mat.roughness = 1;
                    mat.metalness = 0;
                    mat.color = new THREE.Color( 0x5C4830 );
                }
                // Fabric
                if(Mode == 1){
                    mat.roughness = 1;
                    mat.metalness = 0;
                    mat.color = new THREE.Color( 0x101010 );
                }
                // Wood
                if(Mode == 2){
                    mat.roughness = 0.1;
                    mat.metalness = 0;
                    mat.color = new THREE.Color( 0x553c1f );
                }
                
                // Metal
                if(Mode == 3){
                    mat.roughness = 0.3;
                    mat.metalness = 1;
                    mat.color = new THREE.Color( 0xFFFFFF );
                }
                
                // Chrome
                if(Mode == 4){
                    mat.roughness = 0.3;
                    mat.metalness = 1;
                    mat.color = new THREE.Color( 0xFFFFFF );
                }           
                
                // Load Environment -------------------------------------------
                var genCubeUrls = function( prefix, postfix ) {
					return [
						prefix + 'px' + postfix, prefix + 'nx' + postfix,
						prefix + 'py' + postfix, prefix + 'ny' + postfix,
						prefix + 'pz' + postfix, prefix + 'nz' + postfix
					];
				};
                
				var hdrUrls = genCubeUrls( "../../lib/envhdr/", ".hdr" );

				new THREE.HDRCubeTextureLoader().load( THREE.UnsignedByteType, hdrUrls, function ( hdrCubeMap ) {
					var pmremGenerator = new THREE.PMREMGenerator( hdrCubeMap );
					pmremGenerator.update( renderer );
					var pmremCubeUVPacker = new THREE.PMREMCubeUVPacker( pmremGenerator.cubeLods );
					pmremCubeUVPacker.update( renderer );
					hdrCubeRenderTarget = pmremCubeUVPacker.CubeUVRenderTarget;
                    mat.envMap = hdrCubeRenderTarget.texture;
                    mat.needsUpdate = true;
				} );
                
                var textureLoader = new THREE.TextureLoader();
                
                // Load Diffuse;
                var path = "SKUs/" + currentsku +"/" + materialName + "[Diff].jpg";
                var tempMap;
                tempMap = textureLoader.load( path, 
                    function(LoadedTex){
                        mat.color = new THREE.Color( 0xFFFFFF );
                    },
                    function(ProgressTex){
                    },
                    function(ErrorTex){                    
                        mat.map = null;
                        mat.color = new THREE.Color( 0xFFFFFF );
                        mat.needsUpdate = true;
                    }
                );
                
                mat.map = tempMap;

                return mat;
                
           }

            function animate() {
				requestAnimationFrame( animate );
                
				render();
			}
            
            function render() {
				renderer.render( scene, camera );
			}
            
            function onWindowResize() {
				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();
				renderer.setSize( window.innerWidth, window.innerHeight );
			}
              
		</script>

	</body>
</html>
