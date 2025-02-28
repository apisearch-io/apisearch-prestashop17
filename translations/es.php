<?php

global $_MODULE;
$_MODULE = array();
$_MODULE['<{apisearch}prestashop>apisearch_2e5d8aa3dfa8ef34ca5131d20f9dad51'] = 'Configuración';
$_MODULE['<{apisearch}prestashop>apisearch_06aa87356105700329a9636114d765a2'] = 'Activar el buscador en mi tienda';
$_MODULE['<{apisearch}prestashop>apisearch_ec186d203e9a72d3bbef34250ed99cb9'] = 'ID del Índice';
$_MODULE['<{apisearch}prestashop>apisearch_a41071261328cc8380ef9c12763e6fee'] = 'Indexar productos sin imágen';
$_MODULE['<{apisearch}prestashop>apisearch_9ed94ae3bcdde1900ec69fd9d6e76b50'] = 'Indexar productos no disponibles';
$_MODULE['<{apisearch}prestashop>apisearch_26c44fd62f39aa0e883ca89f1a4eafab'] = 'Añadir número de ventas en los productos';
$_MODULE['<{apisearch}prestashop>apisearch_1a442debfe439990c007ba72d0b32beb'] = 'Crearemos una representación de las ventas del producto asignada a un valor del 0 al 1000. En ningún momento enviaremos información real sobre tus ventas.';
$_MODULE['<{apisearch}prestashop>apisearch_494a99d5c7c4053e8fdb56f5130d512f'] = 'Añadir IDs de proveedores';
$_MODULE['<{apisearch}prestashop>apisearch_4036dd19fbe26f37e014bb88d89b41d4'] = 'Añadir descripciones cortas';
$_MODULE['<{apisearch}prestashop>apisearch_499e16b57c50fe993a5bcb5e687a8fc5'] = 'En el caso de que en tus descripciones cortas haya contexto importante del producto, activa esta opción. Añadir la descripción puede, en muchos casos, disminuir la eficiencia de la búsqueda y la calidad de los resultados.';
$_MODULE['<{apisearch}prestashop>apisearch_05933fee152b09de4cc7007780958f2d'] = 'Añadir descripciones largas';
$_MODULE['<{apisearch}prestashop>apisearch_6f004d7ddcbfd3d973fa5ceacc6494ce'] = 'Puedes indexar las descripciones largas en caso de que lo necesites. Este valor tendrá prioridad sobre la descripción corta en caso de que esté seleccionada. Esta opción puede añadir mucho texto irrelevante a la búsqueda y generará long tail en todas las búsquedas, con o sin resultados.';
$_MODULE['<{apisearch}prestashop>apisearch_98ae660e070aac4118b4618ddb9134fd'] = 'Activar B2B';
$_MODULE['<{apisearch}prestashop>apisearch_49279bc316963e9aff1db1460fd7526c'] = 'Muestra unos precios u otros en el buscador dependiendo del usuario que lo visualiza y del grupo al que perteneze. Para testear, puedes forzar la visualización para un grupo de usuarios añadiendo el parámetro apisearch_group_id y/o el parámetro apisearch_customer_id en la URL.';
$_MODULE['<{apisearch}prestashop>apisearch_cc386a578bf90387d4991c3a5b2d0fa7'] = 'Indexar imágenes por color';
$_MODULE['<{apisearch}prestashop>apisearch_448c00326fbf94b280405f07a079dc13'] = 'Si activas un filtro de colores en tu buscador, se mostrará la imagen del color filtrado. En caso contrario, siempre se mostrará la imagen principal';
$_MODULE['<{apisearch}prestashop>apisearch_4c2794db0c12899301ea956b99287a72'] = 'Mostrar precios sin IVA';
$_MODULE['<{apisearch}prestashop>apisearch_95177dc14299e2e57f993023ba664d6d'] = 'Excluir el IVA en los precios del buscador. Este valor se puede sobreescribir en la url del feed añadiendo el valor al parámetro vat';
$_MODULE['<{apisearch}prestashop>apisearch_60685f06115c0e958eecdaf859b21865'] = 'Agrupar variantes por color';
$_MODULE['<{apisearch}prestashop>apisearch_d42f1aac66ee5795ab8e9c52aa1cf910'] = 'Cada grupo de variantes con el mismo color será agrupado en un producto. La imagen que se escogerá para cada uno de los grupos será la imagen de la primera variante del mismo. Asegúrese de que todas las variantes con el mismo color tienen la misma imagen';
$_MODULE['<{apisearch}prestashop>apisearch_460c2634eaaab84607728765982c055a'] = 'Tipo de imágen';
$_MODULE['<{apisearch}prestashop>apisearch_14de2d5b683041953b07bc51ef2f09e5'] = 'Por defecto, la imagen del tipo home_default se utilizará. Cambia este valor si deseas cargar otro tipo de imágen. Solo seleccionables los tipos de imagenes asignadas a productos';
$_MODULE['<{apisearch}prestashop>apisearch_da3ad3b4322b19b609e4fa9d0a98a97b'] = 'Ordenar productos';
$_MODULE['<{apisearch}prestashop>apisearch_a84a585e060373cbe0329749c5653c77'] = 'Orden por defecto de los productos en el buscador. Sin aplicar ningún orden en específico, los productos se mandarán a Apisearch ordenados según configuración. Además, en última instancia, cuando dos productos tengan el mismo score, este orden se aplicará también.';
$_MODULE['<{apisearch}prestashop>apisearch_639f40c2a6a9dbeddc9114253f1ac580'] = 'De más viejo a más nuevo';
$_MODULE['<{apisearch}prestashop>apisearch_637c7cf48a31ea9cdc9496be9373da44'] = 'De más nuevo a más viejo';
$_MODULE['<{apisearch}prestashop>apisearch_890a44bb38e82f7bfa0458465d1bb44f'] = 'Por stock';
$_MODULE['<{apisearch}prestashop>apisearch_85c2dc7948fc1ae27a9254d86167589a'] = 'Por número total de ventas';
$_MODULE['<{apisearch}prestashop>apisearch_da5b0d4e44328191769f083a2fde3bdb'] = 'Primero los actualizados recientemente';

$_MODULE['<{apisearch}prestashop>apisearch_5f853e04d442c97ef1e10e03e9c377bc'] = 'Activar precios en tiempo real';
$_MODULE['<{apisearch}prestashop>apisearch_188c0026d43a98849b74ab9619037c05'] = 'Para cada producto que se muestre en el buscador, Apisearch calculará los precios dinámicamente según el usuario. Este proceso requiere utilizar tu Prestashop en tiempo real, por lo que afectará directamente en la performance de la búsqueda.';
$_MODULE['<{apisearch}prestashop>apisearch_27e3b1c1c34fd90dad6f194bad153e34'] = 'Grupos en los que visualizar precios sin IVA';
$_MODULE['<{apisearch}prestashop>apisearch_3274fd9ead6b6ab50b1fc5f7c87ee1bc'] = 'Independientemente de que un grupo esté configurado con o sin IVA, puedes forzar que los usuarios de dicho grupo muestren los precios sin IVA en el buscador. Requiere de la activación de precios en tiempo real';

$_MODULE['<{apisearch}prestashop>apisearch_a6105c0a611b41b08f1209506350279e'] = 'Si';
$_MODULE['<{apisearch}prestashop>apisearch_7fa3b767c460b54a2be4d49030b349c7'] = 'No';
$_MODULE['<{apisearch}prestashop>apisearch_43781db5c40ecc39fd718685594f0956'] = 'Guardar';
$_MODULE['<{apisearch}prestashop>apisearch_11500d04ea3917407ef54c840977b973'] = 'Panel de administración de Apisearch';


$_MODULE['<{apisearch}prestashop>apisearch_62e5cde27ad49944de104bbe346fd8e8'] = 'Integra Apisearch en tu tienda para una búsqueda rápida y efectiva, mejorando así la experiencia de tus usuarios y aumentando tus ventas.';