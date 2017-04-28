<?php

$pesquisas['teste'] = array(
  'titulo' => "Desdobramento de periódicos",
  'sql'  => <<<EOF
    select * from usp50.z30 where rownum < 3000
EOF
);

$pesquisas['desdobramento_periodicos'] = array(
  'titulo' => "Desdobramento de periódicos",
  'sql'  => <<<EOF
select z30_sub_library, z30_material, count (*) q 
  from usp50.z30 
  where (z30_material = 'ISSUE' OR z30_material = 'ISSBD') AND z30_arrival_date != '00000000' AND z30_update_date BETWEEN 20131201 AND 20141231
group by z30_sub_library, z30_material
EOF
);

$pesquisas['desdobramento_periodicos_periodo'] = array(
  'titulo' => "Desdobramento de periódicos por período",
  'sql' => <<<EOF
  select z30_sub_library, z30_material, count (*) q 
  from usp50.z30 
  where (z30_material = 'ISSUE' OR z30_material = 'ISSBD') AND z30_arrival_date != '00000000' AND z30_update_date BETWEEN 20131201 AND 20141231
group by z30_sub_library, z30_material
EOF
);

// print_r($pesquisas);

