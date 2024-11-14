<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- http://127.0.0.1:5500/index.html -->
    <link rel="stylesheet" href="./css/style-preview.css">
    <link rel="icon" href="/img/icons_logo/icon_white.ico" type="image/x-icon">
    <title>VacAction | Software</title>
</head>

<body id="body">
    <section id="header">
        <header id="sectores_header">
            <div id="sector_1_header">
                <a href="/index.php"><img src="./img/icons_logo/icon_white.ico" alt="Page icon"></a>
            </div>
            <div id="sector_2_header">
                <a href="/html/Empresa.html">
                    <p>Empresa</p>
                </a>
                <a href="/html/Noticias_industria.html">
                    <p>Noticias de la Industria</p>
                </a>
                <a href="/html/Notas_de_version.html">
                    <p>Notas de version</p>
                </a>
                <a href="/html/Blog.html">
                    <p>Blog</p>
                </a>
                <a href="/html/Soporte.html">
                    <p>Soporte</p>
                </a>
            </div>
            <div id="sector_3_header">
                <button id="listas_sesion_sector_3_header" onclick="toggleDropdown()">
                    <div>
                        <img src="./img/content/icono-cuenta-3-removebg-preview.png" alt="Account icon">
                    </div>
                    <div id="flecha_icono">
                        <img src="./img/header/flecha-para-abajo.png" alt="Dropdown date">
                    </div>
                </button>
                <div id="dropdown-menu">
                    <a href="php/login.php">Iniciar sesion</a>
                    <a href="php/register.php">Registrarse</a>
                    <a href="html/Soporte.html">Ayuda</a>
                </div>
            </div>
        </header>
    </section>

    <section id="contenido_1">
        <div id="contenido_1_seccion_1">
            <div id="sector_1_CS_1">
                <div id="primera_vista_parte_superior_CS_1">
                    <p>
                        Software gratuito centrado en RRHH <br> para la adminsitracion de <br> vacaciones.
                    </p>
                </div>
                <div id="segunda_vista_parte_inferior_CS_1">
                    <p>
                        ¿Buscas maximizar rendimiento y adminstrar el tiempo de vacaciones de tu compania? 
                        <br> 
                        Esta puede ser tu solucion.
                    </p>
    
                </div>
            </div>
            <div id="sector_2_CS_1">
                <div>
                    <p><img src="./img/demostracion.png" alt="">
                </div>
            </div>
        </div>
    </section>

    <section id="footer">
        <footer id="sectores_footer">
            <div id="sector_1_footer">
            </div>
            <div id="sector_2_footer">
                <p>Derechos reservados : VacAction ©</p>
            </div>
            <div id="sector_3_footer">
            </div>
        </footer>
    </section>
    <script src="./js/scrip-preview.js"></script>
</body>

</html>