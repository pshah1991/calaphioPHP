{*
 * $Revision: 1.46 $
 * If you want to customize this file, do not edit it directly since future upgrades
 * may overwrite it.  Instead, copy it into a new directory called "local" and edit that
 * version.  Gallery will look for that file first and use it if it exists.
 *}
<form action="{g->url}" method="post">
  <div id="gsContent" class="gcBorder1">
    <div class="gbBlock gcBackground1">
      <h2>
	{g->text text="%s Search Results for " arg1=$SearchShowAll.moduleInfo.name}
	'{$form.searchCriteria}'
      </h2>
    </div>

    {g->hiddenFormVars}
    <input type="hidden"
     name="{g->formVar var="controller"}" value="{$SearchShowAll.controller}"/>
    <input type="hidden" name="{g->formVar var="form[formName]"}" value="SearchShowAll"/>
    <input type="hidden" name="{g->formVar var="form[moduleId]"}" value="{$form.moduleId}"/>
    <input type="hidden" name="{g->formVar var="form[page]"}" value="{$form.page}"/>

    <script type="text/javascript">
      // <![CDATA[
      function setCheck(val) {ldelim}
	{foreach from=$SearchShowAll.moduleInfo.options key=optionId item=optionInfo}
	  document.getElementById('cb_{$optionId}').checked = val;
	{/foreach}
      {rdelim}

      function invertCheck() {ldelim}
	var o;
	{foreach from=$SearchShowAll.moduleInfo.options key=optionId item=optionInfo}
	  o = document.getElementById('cb_{$optionId}'); o.checked = !o.checked;
	{/foreach}
      {rdelim}
      // ]]>
    </script>

    <div class="gbBlock">
      <input type="text" size="50"
       name="{g->formVar var="form[searchCriteria]"}" value="{$form.searchCriteria}"/>
      <input type="hidden"
       name="{g->formVar var="form[lastSearchCriteria]"}" value="{$form.searchCriteria}"/>
      <input type="submit" class="inputTypeSubmit"
       name="{g->formVar var="form[action][search]"}" value="{g->text text="Search"}"/>

      {if isset($form.error.searchCriteria.missing)}
      <div class="giError">
	{g->text text="You must enter some text to search for!"}
      </div>
      {/if}

      <div style="margin: 0.5em 0">
	{foreach from=$SearchShowAll.moduleInfo.options key=optionId item=optionInfo}
	  <input id="cb_{$optionId}" type="checkbox"
	   name="{g->formVar var="form[options][`$SearchShowAll.moduleId`][$optionId]"}"
	   {if isset($form.options[$SearchShowAll.moduleId].$optionId)}checked="checked"{/if}/>
	  <label for="cb_{$optionId}">
	    {$optionInfo.description}
	  </label>
	{/foreach}
      </div>

      <div>
	<a href="javascript:setCheck(1)">{g->text text="Check All"}</a>
	&nbsp;
	<a href="javascript:setCheck(0)">{g->text text="Uncheck All"}</a>
	&nbsp;
	<a href="javascript:invertCheck()">{g->text text="Invert"}</a
      </div>
    </div>

    {if !empty($SearchShowAll.results)}
      <div class="gbBlock">
	<div>
	  <input type="submit" class="inputTypeSubmit"
	   name="{g->formVar var="form[action][scan]"}"
	   value="{g->text text="Search All Modules"}"/>
	</div>
	<h4>
	  {$SearchShowAll.moduleInfo.name}
	  {if ($SearchShowAll.results.count > 0)}
	    {g->text text="Results %d - %d of %d, Page %d of %d"
	     arg1=$SearchShowAll.results.start arg2=$SearchShowAll.results.end
	     arg3=$SearchShowAll.results.count arg4=$form.page arg5=$SearchShowAll.maxPages}
	  {/if}
	  {if ($form.page > 1)}
	    <input type="submit" class="inputTypeSubmit"
	     name="{g->formVar var="form[action][previousPage]"}"
	     value="{g->text text="&laquo; Back"}"/>
	  {/if}
	  {if ($form.page < $SearchShowAll.maxPages)}
	    <input type="submit" class="inputTypeSubmit"
	     name="{g->formVar var="form[action][nextPage]"}"
	     value="{g->text text="Next &raquo;"}"/>
	  {/if}
	</h4>

	{if (sizeof($SearchShowAll.results.results) > 0)}
	  {assign var="childrenInColumnCount" value=0}
	  <table id="gbThumbMatrix"><tr>
	    {foreach from=$SearchShowAll.results.results item=result}
	      {* Move to a new row *}
	      {if ($childrenInColumnCount == 4)}
		</tr><tr>
		{assign var="childrenInColumnCount" value=0}
	      {/if}

	      {assign var=childrenInColumnCount value="`$childrenInColumnCount+1`"}
	      {assign var=itemId value=$result.itemId}
	      <td class="{if
	       $SearchShowAll.items.$itemId.canContainChildren}gbItemAlbum{else}gbItemImage{/if}"
	       style="width: 10%">
		<a href="{g->url arg1="view=core.ShowItem" arg2="itemId=$itemId"}">
		{if isset($SearchShowAll.thumbnails.$itemId)}
		  {g->image item=$SearchShowAll.items.$itemId
			    image=$SearchShowAll.thumbnails.$itemId class="giThumbnail"}
		{else}
		  {g->text text="No thumbnail"}
		{/if}
		</a>
		<ul class="giInfo">
		  {foreach from=$result.fields item=field}
		  <li>
		    {$field.key}:
		    {$field.value|default:"&nbsp;"|ireplace:$form.searchCriteria:"<span class=\"giSearchHighlight\">\\1</span>"|markup}
		  </li>
		  {/foreach}
		</ul>
	      </td>
	    {/foreach}

	    {* flush the rest of the row with empty cells *}
	    {section name="flush" start=$childrenInColumnCount loop=4}
	      <td>&nbsp;</td>
	    {/section}
	  </tr></table>
	{else}
	  <p class="giDescription">
	    {g->text text="No results found for"} '{$form.searchCriteria}'
	  </p>
	{/if}
      </div>
    {/if}
  </div>
</form>
