<?php
use Srms\Application as App;

function field(string $name,string $label,mixed $value='',string $type='text',bool $required=false,array $options=[]): void
{
    echo '<label class="field"><span>'.e($label).($required?' <b aria-hidden="true">*</b>':'').'</span>';
    if($type==='select'){
        echo '<select name="'.e($name).'" '.($required?'required':'').'><option value="">Select…</option>';
        foreach($options as $key=>$text)echo '<option value="'.e($key).'" '.((string)$key===(string)$value?'selected':'').'>'.e($text).'</option>';
        echo '</select>';
    }elseif($type==='textarea'){
        echo '<textarea name="'.e($name).'" rows="4" '.($required?'required':'').'>'.e($value).'</textarea>';
    }elseif($type==='checkbox'){
        echo '<span class="check"><input type="checkbox" name="'.e($name).'" value="1" '.($value?'checked':'').'> Enabled</span>';
    }else{
        $attributes=[];
        if($required)$attributes[]='required';
        if($type==='number')$attributes[]='step="any"';
        if($type==='password')$attributes[]='minlength="12" autocomplete="new-password"';
        echo '<input type="'.e($type).'" name="'.e($name).'" value="'.e($value).'" '.implode(' ',$attributes).'>';
    }
    echo '</label>';
}

function form_start(string $route,bool $file=false): void
{
    echo '<form method="post" action="'.e(url($route)).'" '.($file?'enctype="multipart/form-data"':'').' class="form-grid">'.csrf_field();
}
function hidden(string $name,mixed $value): void {echo '<input type="hidden" name="'.e($name).'" value="'.e($value).'">';}
function submit(string $text='Save changes'): void {echo '<div class="form-actions"><button class="button primary" type="submit">'.e($text).'</button></div></form>';}
function badge(mixed $status): string
{
    $value=(string)$status;
    $class=in_array($value,['paid','posted','active','approved','completed','imported'],true)?'green':(in_array($value,['reversed','invalid','failed','archived','closed'],true)?'red':'gold');
    return '<span class="badge '.e($class).'">'.e(ucwords(str_replace('_',' ',$value))).'</span>';
}
function data_table(array $rows,?array $columns=null): void
{
    if(!$rows){echo '<div class="empty-state"><span aria-hidden="true">◇</span><h3>No records found</h3><p>Adjust the filters or create the first record for this section.</p></div>';return;}
    $columns??=array_combine(array_keys($rows[0]),array_map(fn($v)=>ucwords(str_replace('_',' ',$v)),array_keys($rows[0])));
    echo '<div class="table-wrap"><table><thead><tr>';
    foreach($columns as $label)echo '<th scope="col">'.e($label).'</th>';
    echo '</tr></thead><tbody>';
    foreach($rows as $row){echo '<tr>';foreach($columns as $key=>$label){$value=$row[$key]??null;echo '<td>'.($key==='status'?badge($value):e($value===null||$value===''?'—':$value)).'</td>';}echo '</tr>';}
    echo '</tbody></table></div>';
}
function page_heading(string $title,string $description): void
{
    echo '<div class="page-heading"><div><p class="eyebrow">EDDT · SPATIAL REVENUE MANAGEMENT</p><h1>'.e($title).'</h1><p>'.e($description).'</p></div></div>';
}
