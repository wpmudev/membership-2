------------------------------------------------------------------------------
--                   INSTRUCTION TO TRANSLATE THIS PLUGIN                   --
------------------------------------------------------------------------------

.po or .mo

    WordPress needs the .mo file to translate the plugin.
    The .po file is only used by tools like POEdit to create the .mo file.

Correct filename

    Translation files must have the name "membership2-<locale>.mo"

    Example translations:
        membership2-fr_FR.mo  (french translation for France)
        membership2-fr_CH.mo  (french translation for Switzerland)
        membership2-de_CH.mo  (german translation for Switzerland)

Directory

    Save the .mo file with the correct filenmae to either of these two directories:
    
    /wp-content/languages/plugins/<mo-file>                <-- Best choice!
    /wp-content/plugins/membership/languages/<mo-file>     <-- Not recommented
  
    The second option is not recommented because your custom translation will be lost when you update the plugin.