<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Vizar WebView</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
        <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet"> 
		<style>
			body {
				font-family: Monospace;
				background-color: #000000;
				color: #fff;
				margin: 0px;
				overflow: hidden;
			}
            
            .loading {
              position: fixed;
              z-index: 999;
              height: 100%;
              width: 100%;
              overflow: show;
              margin: auto;
              top: 0;
              left: 0;
              bottom: 0;
              right: 0;
              background-color: #4f4f4f;
            }

            .outPopUp {
              position: absolute;
              width: 250px;
              height: 200px;
              z-index: 15;
              top: 30%;
              left: 50%;
              overflow: show;
              margin: -100px 0 0 -125px;
            }
            
            .bottom-right{
                right: 10px;
                bottom: 10px;
                width: 42px;
                height: 42px;
                position: absolute;
                background-image: url('logo2.png');
            }
            
            .bottom-right:hover{
                background-image: url('logo.png');
            }

		</style>
	</head>

	<body>
        
        
        <script src="lib/three.js"></script>
        <script src="lib/jquery-3.2.1.min.js"></script>
        
        <script src="js/controls/OrbitControls.js"></script>
        <script src="js/loaders/RGBELoader.js"></script>
		<script src="js/loaders/HDRCubeTextureLoader.js"></script>

		<script src="js/loaders/MTLLoader.js"></script>
		<script src="js/loaders/OBJLoader.js"></script>
		<script src="js/Detector.js"></script>
        
        <script src="js/pmrem/PMREMGenerator.js"></script>
        <script src="js/pmrem/PMREMCubeUVPacker.js"></script>
        
        <?php
            $clientId = $_GET["vendor"];
            $productId = $_GET["id"];
            $skuId = $_GET["sku"];
            $wl = $_GET["wl"];
            
            if(empty($_GET["sku"])){$skuId = "1";}
            if(empty($_GET["wl"])){$wl = "0";}
            
            echo "<script>".
            
            "var clientId =".$clientId.";". 
            "var skuId =".$skuId.";". 
            "var productId =".$productId.";".
            "var wl =".$wl.";".             
            "</script>"
        ?>
        <div id="webview"></div>
        
        <div id="loading" class="loading">
            <div class= outPopUp>
                <p align="center" style="font-family: 'Roboto', sans-serif; font-size: 130%;"> Visualizador 3D Interativo </p>
                <div style="background-color:#4f4f4f;border-style: solid;border-color: #FFFFFF;border-width: Thin; border-radius: 10px;">
                    <div id="progress" style="height:10px;width:10%;background-image: url('load.png');border-radius: 10px;"></div>
                    
                </div> 
                <img id="howto" src="blank.png" align="middle" height="285" width="250" ></img>
            </div>
        </div>
        
        <script> 
            (function animateBG() {
                $('#progress').animate({
                    backgroundPosition: '+=1'
                }, 12, animateBG);
            })();
            
        </script>
        
        <script>         
            var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            if (isMobile) {
                document.getElementById("howto").src ="_m.png";
            }
            else{
                document.getElementById("howto").src ="_d.png";
            }
        </script>
        

        
		<script>
            
			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();
            
            // System
			var container, stats, controls;
			var camera, scene, renderer, light;
			var clock = new THREE.Clock();
            
            var LoadManager = new THREE.LoadingManager();
            
            // Scene
            var currentObject = null;
            var groundMat;
            
            // currentsku declared in php based on the page id
            
			init();

			function init() {
                
                // SETUP WINDOW -------------------------------------------
				//container = document.createElement( 'webview' );
                container = document.getElementById("webview");
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
                controls.dampingFactor = 0.25;
                controls.minDistance = 2;
                controls.maxDistance = 5;
                controls.enablePan = false;
                controls.minPolarAngle = -Math.PI/2;
                controls.maxPolarAngle = Math.PI/2;
                controls.update();
                
                // LOAD MANAGER -------------------------------------------
                LoadManager.onStart = function ( url, itemsLoaded, itemsTotal ) {
                };
                LoadManager.onLoad = function ( ) {
                    // Finished Texture Loader, remove load and show scene
                    document.getElementById("loading").remove();
                    document.body.appendChild( container );
                    animate();
                };
                LoadManager.onProgress = function ( url, itemsLoaded, itemsTotal ) {
                    document.getElementById("progress").style.width = itemsLoaded/itemsTotal*100 + "%";
                };
                
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
				dirLight.shadow.mapSize.width = 512;
				dirLight.shadow.mapSize.height = 512;
				
                var d = 3;
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
				
                
                // SKU OBJECT LOADER ------------------------------------
                LoadSKUObject()

                // RENDER SETTINGS -------------------------------------------
                renderer.gammaInput = true;
				renderer.gammaOutput = true;
                
                //renderer.shadowMapType = THREE.PCFSoftShadowMap;
                renderer.shadowMap.enabled =  true;
				renderer.shadowMap.renderReverseSided = false;
                renderer.shadowMap.autoUpdate = false;
				
			}

            function LoadSKUObject(){
                // Clear the current object to load another SKU hopping the cache handle to not load it again
                if(currentObject != null){
                    scene.remove(currentObject);
                    currentObject = null;
                }
                
                
                
                var mtlLoader = new THREE.MTLLoader();
                mtlLoader.load( "db/" + clientId + "/" + productId + "/model.mtl", function( materials ) {
                
					materials.preload();
                    
					var objLoader = new THREE.OBJLoader();
					objLoader.setMaterials( materials );

					objLoader.load( "db/" + clientId + "/" + productId + "/model.obj", function ( object ) {
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
                    mat.roughness = 0.5;
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
                
				var hdrUrls = genCubeUrls( "lib/envhdr/", ".hdr" );

				new THREE.HDRCubeTextureLoader().load( THREE.UnsignedByteType, hdrUrls, function ( hdrCubeMap ) {
					var pmremGenerator = new THREE.PMREMGenerator( hdrCubeMap );
					pmremGenerator.update( renderer );
					var pmremCubeUVPacker = new THREE.PMREMCubeUVPacker( pmremGenerator.cubeLods );
					pmremCubeUVPacker.update( renderer );
					hdrCubeRenderTarget = pmremCubeUVPacker.CubeUVRenderTarget;
                    mat.envMap = hdrCubeRenderTarget.texture;
                    mat.needsUpdate = true;
				} );
                
                var textureLoader = new THREE.TextureLoader(LoadManager);
                
                // Load Diffuse;
                var path = "db/" + clientId + "/" + productId + "/" + "/skus/" + skuId + "/" + materialName + "[Diff].jpg";
                var tempMap;
                tempMap = textureLoader.load( path, 
                    function(LoadedTex){
                        mat.color = new THREE.Color( 0xFFFFFF );
                        console.log("Sucess Loading Diffuse " + materialName);
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
                
                // Load Normal;
                var path = "db/" + clientId + "/" + productId + "/" + "/skus/" + skuId + "/" + materialName + "[Norm].jpg";
                var tempNorm;
                tempNorm = textureLoader.load( path, 
                    function(LoadedTex){
                        console.log("Sucess Loading Normal " + materialName);
                    },
                    function(ProgressTex){
                    },
                    function(ErrorTex){
                        console.log("Error On Normal " + materialName);
                        mat.normalMap = null;
                        mat.needsUpdate = true;
                    }
                );
                
                mat.normalMap = tempNorm;
                
                // Load Roughness;
                var path = "db/" + clientId + "/" + productId + "/" + "/skus/" + skuId + "/" + materialName + "[Rough].jpg";
                var tempRoug;
                tempRoug = textureLoader.load( path, 
                    function(LoadedTex){
                        console.log("Sucess Loading Roughness " + materialName);
                    },
                    function(ProgressTex){
                    },
                    function(ErrorTex){
                        mat.roughnessMap = null;
                        mat.needsUpdate = true;
                    }
                );
                
                mat.roughnessMap = tempRoug;
                
                // Load Metalness;
                var path = "db/" + clientId + "/" + productId + "/" + "/skus/" + skuId + "/" + materialName + "[Metal].jpg";
                var tempMetal;
                tempMetal = textureLoader.load( path, 
                    function(LoadedTex){
                        console.log("Sucess Loading Metalness " + materialName);
                    },
                    function(ProgressTex){
                    },
                    function(ErrorTex){
                        mat.metalnessMap = null;
                        mat.needsUpdate = true;
                    }
                );
                
                mat.metalnessMap = tempMetal;
                
                
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
        
      
        
        <div id="whitelabel">
            <a href="http://www.viz-ar.com" target="_blank">
                <div  class = "bottom-right"></div>
            </a>
        </div>
        
        <script>
            console.log(wl);
            if (wl == "1") {
                console.log(wl);
                document.getElementById("whitelabel").remove();
            }
            
        </script>
        
	</body>
</html>
