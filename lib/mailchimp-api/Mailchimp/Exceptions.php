<?php

class M2_Mailchimp_Error extends Exception {}
class M2_Mailchimp_HttpError extends M2_Mailchimp_Error {}

/**
 * The parameters passed to the API call are invalid or not provided when required
 */
class M2_Mailchimp_ValidationError extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_ServerError_MethodUnknown extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_ServerError_InvalidParameters extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Unknown_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Request_TimedOut extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Zend_Uri_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_PDOException extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Avesta_Db_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_XML_RPC2_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_XML_RPC2_FaultException extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Too_Many_Connections extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Parse_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_Unknown extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_Disabled extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_DoesNotExist extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_NotApproved extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_ApiKey extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_UnderMaintenance extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_AppKey extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_IP extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_DoesExist extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_InvalidRole extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_InvalidAction extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_MissingEmail extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_CannotSendCampaign extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_MissingModuleOutbox extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_ModuleAlreadyPurchased extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_ModuleNotPurchased extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_User_NotEnoughCredit extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MC_InvalidPayment extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_DoesNotExist extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidInterestFieldType extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidOption extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidUnsubMember extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidBounceMember extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_AlreadySubscribed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_NotSubscribed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidImport extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MC_PastedList_Duplicate extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MC_PastedList_InvalidImport extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Email_AlreadySubscribed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Email_AlreadyUnsubscribed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Email_NotExists extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Email_NotSubscribed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_MergeFieldRequired extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_CannotRemoveEmailMerge extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_Merge_InvalidMergeID extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_TooManyMergeFields extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidMergeField extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_InvalidInterestGroup extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_List_TooManyInterestGroups extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_DoesNotExist extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_StatsNotAvailable extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidAbsplit extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidContent extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidOption extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidStatus extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_NotSaved extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidSegment extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidRss extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidAuto extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MC_ContentImport_InvalidArchive extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_BounceMissing extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Campaign_InvalidTemplate extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_EcommOrder extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_UnknownError extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_UnknownSplitTest extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_UnknownTestType extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_UnknownWaitUnit extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_UnknownWinnerType extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Absplit_WinnerNotSelected extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_Analytics extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_DateTime extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_Email extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_SendType extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_Template extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_TrackingOptions extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_Options extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_Folder extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_URL extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Module_Unknown extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MonthlyPlan_Unknown extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Order_TypeUnknown extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_PagingLimit extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Invalid_PagingStart extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Max_Size_Reached extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_MC_SearchException extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Goal_SaveFailed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Conversation_DoesNotExist extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Conversation_ReplySaveFailed extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_File_Not_Found_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Folder_Not_Found_Exception extends M2_Mailchimp_Error {}

/**
 * None
 */
class M2_Mailchimp_Folder_Exists_Exception extends M2_Mailchimp_Error {}


