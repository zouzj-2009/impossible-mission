Ext.ns('App.locale');
if (!App.locale.text) App.locale.text = {};
App.locale.text = Ext.apply(App.locale.text, {
	'Access': '访问权限',
	'Add New ...': '添加...',
	'Are you sure to logout?': '确认退出么?',
	'Blockdev': '块设备',
	'Broadcast': '广播地址',
	'Busid': '总线ID',
	'CHAP': 'CHAP',
	'Cancel': '取消',
	'Capacity': '容量',
	'Change Password': '修改密码',
	'Change password ...': '修改登录密码...',
	'Change': '修改',
	'Choose Log': '选择日志',
	'Clientip': '客户端IP',
	'Count': '计数',
	'Current License': '当前license',
	'Current Setting': '当前设定',
	'DNS1': 'DNS1',
	'DNS2': 'DNS2',
	'Date': '日期',
	'Default GW1': '默认网关1',
	'Default GW2': '默认网关2',
	'Delete': '删除',
	'Description': '描述',
	'Destinationip': '目标IP',
	'Detail log inforamtion': '详细日志信息',
	'Dev': '设备',
	'Device Name': '设备名',
	'Disk Channel': '磁盘通道',
	'Disks': '磁盘管理',
	'Download All': '下载日志',
	'Download Confirm': '下载确认！',
	'Exclude Client': '直连客户端IP',
	'Excludesource': '直连客户端',
	'Export ...': '导出图形...',
	'Facility': '对象',
	'Fixed': '固定磁盘',
	'GW & DNS': '网关和DNS',
	'Global CHAP Setting': '全局CHAP设定',
	'Global Setting': '全局设定',
	'HBA setting': '磁盘通道设定',
	'Host Setting': '主机设定',
	'Host': '主机',
	'HostName': '主机名',
	'IP Address Configuration': 'IP地址配置',
	'IP address of current visited server %ipaddress% will be deleted, you need restrart the web application, are you sure?': '正在访问的服务器IP %ipaddress% 将被删除，需要使用新的地址重新进入web应用，您确定么？',
	'IPAddress': 'IP地址',
	'Include IP': '物理IP',
	'Includeip': '物理IP',
	'Initiator Pass': '启动器密码',
	'Initiator User': '启动器用户',
	'Initiator': '启动器',
	'Initiatorpass': '启动器密码',
	'Initiatoruser': '启动器用户',
	'Ipaddress': 'IP地址',
	'Ipv6address': 'IPV6地址',
	'LUN Map': 'LUN 映射',
	'Language': '',
	'License': '授权',
	'Line': '行号',
	'Login': '登录',
	'Loging on': '登录到',
	'Logout Confirm': '退出确认',
	'Logout': '退出登录',
	'LunMap': 'LUN映射',
	'Maintain': '系统维护',
	'Mapping CHAP Setting': '映射相关的CHAP设定',
	'Max Count': '最大计数',
	'Message': '日志',
	'NIC & IP Address': '网卡IP设定',
	'NIC Setting': '网卡信息',
	'NIC': '网卡',
	'NetMask': '子网掩码',
	'Netmask': '子网掩码',
	'Network Bandwidth Monitor': '网络流量监视器',
	'Network': '网络设定',
	'New Mapping': '新的映射',
	'New license': '新授权',
	'New password': '新密码',
	'No': '否',
	'OK': '好的',
	'Old Password': '原密码',
	'Old license': '当前授权',
	'Password': '密码',
	'Physicdevice': '物理网卡',
	'Portal IP': '虚拟IP',
	'Portal Mask': '虚拟IP掩码',
	'Product': '产品型号',
	'Re-typing mismatched with new password': '两次输入的新密码不符',
	'Re-typing': '再次输入',
	'Readspeed': '读取速度',
	'Refresh': '刷新',
	'Reload': '重载',
	'Rescan ...': '重新扫描...',
	'Revision': '版本',
	'Rxbytes': '发送字节数',
	'Sample Rate': '采样间隔',
	'Save': '保存',
	'Scsi_device': 'SCSI设备号',
	'Setting': '设定',
	'Sourceip': '源IP',
	'Start Monitor': '开始监控',
	'Stop Monitor': '停止监控',
	'SysInfo': '系统信息',
	'System Information': '系统信息',
	'System Log': '系统日志',
	'Target Pass': '目标密码',
	'Target User': '目标用户',
	'TargetID': '目标ID',
	'TargetName': '目标IQN',
	'Targetid': '目标ID',
	'Targetip': '目标IP',
	'Targetname': '目标IQN',
	'Targetpass': '目标密码',
	'Targetuser': '目标用户',
	'Test ...': '测试 ...',
	'The value in this field is invalid': '本项输入不正确',
	'Time': '时间',
	'TimeZone': '时区',
	'Txbytes': '发送字节数',
	'Type': '类型',
	'Update': '更新',
	'Upload': '上传',
	'Username': '用户',
	'Vendor': '厂家',
	'Virt Portal': '虚拟IP',
	'Virtual Portal': '虚拟IP',
	'Writespeed': '写入速度',
	'Yes': '是的',
	'__x': '',
	'change portalip(%portalip%) to %new_portalip%?': '修改虚拟IP(%portalip%)为%new_portalip%么?',
	'delete exclude(%excludesource%) or include(%includeip%) member??': '删除直连的IP(%excludesource%)或物理IP(%includeip%)设定?',
	'delete': '删除',
	'disconnect': '断开连接',
	'iSCSI Connection': 'iSCSI连接信息',
	'iSCSI': 'iSCSI设定',
	'refresh': '刷新',
	'reload portal config?': '重新载入虚拟IP设定?',
	'reload portal info?': '重新载入虚拟IP设定?',
	'{0} is not a valid date - it must be in the format {1}': '{0} 不是正确的日期格式，必须使用格式 {1}',
'Would you like to download the chart as an image?<br/>Warning! This is a cloud service, Data will be sent to website:sencha.io)': '您希望将本图例保存为本地图像么?<br/>提示! 这是一项云服务，数据将发往网站:sencha.io.',
});
